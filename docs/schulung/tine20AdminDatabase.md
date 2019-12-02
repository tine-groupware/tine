Tine 2.0 Admin Schulung: Datenbank
=================

Version: Caroline 2017.11

Konfiguration und Performance-Optimierung der Datenbank

MySQL Optimierung
=================

siehe auch https://service.metaways.net/Ticket/Display.html?id=150469

## Zusammengefasst:

* der MySQL Server muß entsprechend der Datenmenge und Anwendernutzung dimensioniert sein
* der MySQL Server hat 16 Hardware Threads, innodb_thread_concurrency prüfen und ggf. anpassen. Der Wert sollte mindestens auf 16 gesetzt werden und später mittels Messungen der optimale Wert irgendwo zwischen 16 und 64 gefunden werden.
* die innodb Daten und Index Größe bitte ermitteln und eventuell innodb_buffer_pool_size anpassen
* die tmp table size effektiv auf 64MB setzen (max_heap_table_size und tmp_table_size beide auf 64 MB! Denn min(x,y) greift)
* MySQL < 5.5 ist völlig veraltet und insbesondere die InnoDB performance wurde stark verbessert! Eine aktuelle Version kann leicht 30% Performance gewinnt bringen

## Ausführlich

    SELECT SUM(data_length+index_length) / POWER(1024,3) Total_InnoDB_G FROM information_schema.tables WHERE engine='InnoDB';

Die Performance, besonders von InnoDB, hat sich in den letzten Versionen stark verbessert:
https://www.liquidweb.com/kb/mysql-5-1-vs-5-5-vs-5-6-performance-comparison/

MySQL 5.1.73 – 1818 tps
MySQL 5.5.39 – 2978 tps
MySQL 5.6.21 – 2830 tps
(from: https://blog.dbi-services.com/mysql-versions-performance-comparison/)


Bei 1,8% der Select statements wird eine temp table auf der HDD angelegt:

    'Com_select', '131.924.697'
    'Created_tmp_disk_tables', '2.432.327'

    tmp_table_size = 33554432
    'max_heap_table_size', '16777216'

=> max memory tmp table size ist aktuell 16 MB (nicht 32!)

Beide Variablen können zur Laufzeit geändert werden.

    SET GLOBAL max_heap_table_size = 67108864
    SET GLOBAL tmp_table_size = 67108864

Bitte erst

    Show Variables LIKE 'Com_select';
    Show Variables LIKE 'Created_tmp_disk_tables';

ausführen und das Ergebnis notieren. Dann die tmp table Größe auf 64 MB ändern und 3-4 Werktage später bitte erneut die Variablen com_select und cretaed_tmp_disk_tables auslesen. Die Ratio sollte sich verbessert haben. Die zur Laufzeit geänderten Variablen am besten auch in der my.cnf konfigurieren.

die innodb_buffer_pool_size ist auf 2 G eingestellt. Das scheint mir relativ wenig. Mit diesem Query berechnet man die RIBPS (recommended innodb_buffer_pool_size). Der Query nimmt die Größe aller innodb Tabellen und deren Indexe mit dem Faktor 1.6 mal (um Platz für Wachstum einzukalkulieren)

    SELECT CEILING(Total_InnoDB_Bytes*1.6/POWER(1024,3)) RIBPS FROM (SELECT SUM(data_length+index_length) Total_InnoDB_Bytes FROM information_schema.tables WHERE engine='InnoDB') A;

oder alternativ die minimal Einstellung ohne Wachstum mit Faktor 1.1:

    SELECT CEILING(Total_InnoDB_Bytes*1.1/POWER(1024,3)) RIBPS FROM (SELECT SUM(data_length+index_length) Total_InnoDB_Bytes FROM information_schema.tables WHERE engine='InnoDB') A;

Das Ergbnis ist der aufgerundete innodb_buffer_pool_size Wert in "G". Da die Maschine nur 8GB RAM hat (was für eine DB Maschine relativ wenig ist), ist natürlich nur ein maximaler Wert von 5-6 G möglich. Sollte der RIBPS Wert mit Faktor 1.1 größer sein als der verfügbare RAM würde ich dringend zur Nachrüstung der Hardware raten.

Sollte der RIBPS(1.1) Wert <=5-6G betragen und es möglich sein innodb_buffer_pool_size auf RIBS(1.1) zu setzen, so würde ich folgendes Vorgehen vorschlagen:
die aktuelle Cache Hit Ratio berechnen und uns bitte diese Werte und das Datum der Erhebung schicken:
show status like 'innodb_buffer_pool_reads';
show status like 'innodb_buffer_pool_read_requests';

Anhand der Werte vom ~04.07.

    'Innodb_buffer_pool_read_requests', '155543083345'
    'Innodb_buffer_pool_reads', '154073779'

können wir damit die aktuelle Cache Hit Ratio des letzten ~Monats berechnen. (Scheint wohl ~90% zu sein, als Vergleichswert, auf dem System auf dem die Metaways eigene tine20 Instanz läuft ist der Wert 99,999%! 90% Cache Hit Ratio ist nicht gut)

Möglichst Zeitnah (<1 Stunde) den innodb_buffer_pool_size Wert auf RIBPS(1.1) (oder höher) setzen. 3-4 Werktage später bitte wieder die aktuelle Cache Hit Ratio Werte auslesen:

    show status like 'innodb_buffer_pool_reads';
    show status like 'innodb_buffer_pool_read_requests';

Der absolute Wert wird natürlich bei ~90% bleiben, aber verglichen mit den Werten die vorher erhoben wurden und der Berechnung der Ratio über die Differenz der Werte sollte sich ein Wert um 99% ergeben.

Sollte es auf Grund des knappen RAMs von 8GB nicht möglich sein den innodb_buffer_pool_size Wert auf RIBPS(1.1) (oder höher) zu setzen, so gibt es noch Luft für Fine Tuning (die slow logs legen nahe das es zu einzelnen Last Spitzen kommt. Eventuell können diese durch eine Erhöhung des innodb_thread_concurrency Wertes besser bewältigt werden da davon auszugehen ist das einige Threads auf IO warten und daher noch genug CPU vorhanden ist um weitere Threads, die eventuell ohne HDD IO auskommen, zu bearbeiten.

Fine Tuning wird aber keine großen Sprünge schaffen, es ist nur Fine Tuning. Eine performante DB braucht zwingend RAM > Datenbankgröße (Daten+Indexe).

## MySQL unter Ubuntu 16.04+

    profiles::databases::mysql::limit_nofile_systemd: 100000

Für jeden Prod-DB-Server sollte das (ab Xenial) mindestens auf 100000 gesetzt werden, wenn nicht höher. Sonst werden bestimmte Einstellungen von Mysql massiv runtergetunt. Namentlich betrifft das max_connections und table_open_cache.

## MySQL-Tuner

https://www.howtoforge.com/tuning-mysql-performance-with-mysqltuner
bzw. https://raw.githubusercontent.com/major/MySQLTuner-perl/master/mysqltuner.pl


DB-Schema Vergleich und Aktualisierung
=================

Es kann passieren, dass das Schema der Datenbank nicht mehr dem aktuellen Stand
 entspricht. Das betrifft vor allem alte Installationen bzw. Installationen bei
 denen ein Admin selbst Hand angelegt hat und z.B. Update-Skripte übersprungen
 wurden. Um das Schema wieder an den normalen Stand anzugleichen, müssen folgende
 Schritte durchgeführt werden:
 
* Backup der bestehenden Datenbank
* Installation einer parallelen Installation mit der gleichen Version
* Der DB-Benutzer der Vergleichsinstallation muss die gleichen Zugangsdaten haben
* Einspielen einer gepatchten tine20/vendor/doctrine/dbal/lib/Doctrine/DBAL/Schema/AbstractAsset.php
 (siehe https://forge.tine20.org/view.php?id=13702)
* Aufruf des Datenbankvergleichs:

    $ php setup.php --compare -- otherdb=tine20comparedb
    
* Jetzt können die SQL-Statements ausgeführt werden, die zum aktualisieren auf
 das Schema der Vergleichsdatenbank benötigt werden. Manchmal muss das Kommando
 mehrmals aufgerufen werden
 
Mit "tinyint"-Feldern scheint es ein Problem zu geben, diese werden wohl nicht
 entsprechend aktualisiert:
 
    root:/var/www/tine20# php setup.php --compare -- otherdb=tine20demo
    Array
    (
        [0] => ALTER TABLE `tine20_preferences` CHANGE `personal_only` `personal_only` TINYINT(1) DEFAULT NULL
        [1] => ALTER TABLE `tine20_sales_cost_centers` CHANGE `is_deleted` `is_deleted` TINYINT(1) DEFAULT '0'
        [2] => ALTER TABLE `tine20_tree_nodes` CHANGE `islink` `islink` TINYINT(1) DEFAULT '0' NOT NULL
    )

Volltext-Indizierung in der Datenbank
=================

Tine 2.0 unterstützt die Volltext-Indizierung ab Mysql 5.6.4 bzw. MariaDB 10.1

Wenn man von einer älteren Version (oder z.B. PGSQL) kommt, kann man das Feature so nachinstallieren:

    $ php setup.php --upgradeMysql564

Konfiguration des Datenbank-Ports auf Localhost
=================

Durch einen Bug in PHP-PDO ist es nicht möglich den Port auf einen anderen, als den Default-Port zu setzen,
 wenn als Host "localhost" eingetragen ist. Bei Non-Default-Ports muss dann die IP-Adresse (z.B. 127.0.0.1)
 verwendet werden.
 
Migration PostgreSQL (PGSQL) -> MySQL
=================

Ab Version 2018.11 wird PGSQL nicht mehr unterstützt.

Die Migration muss in 2017.11 durchgeführt werden. Am besten in Version 2017.11.13 (oder früher) wegen Änderungen 
in Tinebase/Setup/Update/Release10.php, die nicht PGSQL-kompatibel sind...
Die Migration muss mit der gleichen Version gemacht werden, mit der Tine 2.0 mit PGSQL gerade läuft, da
sonst das DB-Schema möglicherweise nicht passt.

Migration ist u.a. Thema in diesem Ticket: #175111: [Phoenix] Unsere Instanz Tine 2.0 (UCS)
Ausserdem gibt es eine (nicht besonders gute) Anleitung zur manuellen Migration von Files/Hour im github:
https://github.com/tine20/tine20/wiki/DE%3AMigration-von-Postgres-nach-MySQL

1) Installation von MySQL (empfohlen 5.7+) oder MariaDB (empfohlen 10.2+)

2) Installation des php-mysql Moduls falls noch nicht vorhanden

3) Anlegen von Datenbank (z.b. 'tine20') und eines Benutzers, mit dem auf DB zugegriffen werden kann

4) Anlegen einer Konfigurationsdatei mysqlconf.php mit folgendem Inhalt:

```php
<?php
return array (
    'host' => 'TINE20_DBHOST',
    'dbname' => 'TINE20_DBNAME',
    'username' => 'TINE20_DBUSER',
    'password' => 'TINE20_DBPASSWD',
    'tableprefix' => 'tine20_',
    'adapter' => 'pdo_mysql',
);
```

5) Aufruf des Migrationsskriptes

(mysqlConfigFile muss mit dem absoluten Pfad referenziert sein)

    $ php /usr/share/tine20/sezup.php --config /etc/tine20 --pgsqlMigration -- mysqlConfigFile=/path/to/mysqlconf.php

6) nach der Migration der Daten dann in der Tine 2.0 config.inc.php die neue DB-Konfiguration (analog mysqlconf.php) eintragen.

Anschliessend sollte das Update ohne Probleme durchlaufen.
