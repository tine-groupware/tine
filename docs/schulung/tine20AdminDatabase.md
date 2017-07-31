Tine 2.0 Admin Schulung: Datenbank
=================

Version: Egon 2016.11

Konfiguration und Performance-Optimierung der Datenbank

MySQL Optimierung
=================

siehe auch https://service.metaways.net/Ticket/Display.html?id=150469

Zusammengefasst:
* der MySQL Server muß entsprechend der Datenmenge und Anwendernutzung dimensioniert sein
* der MySQL Server hat 16 Hardware Threads, innodb_thread_concurrency ist aber nur auf 8. Der Wert sollte auf 16 geändert werden und später mittels Messungen der optimale Wert irgendwo zwischen 16 und 64 gefunden werden.
* die innodb Daten und Index Größe bitte ermitteln und eventuell innodb_buffer_pool_size anpassen
* die tmp table size effektiv auf 64MB setzen (max_heap_table_size und tmp_table_size beide auf 64 MB! Denn min(x,y) greift)
* MySQL 5.1.73 ist völlig veraltet und insbesondere die InnoDB performance wurde stark verbessert! Eine aktuelle Version kann leicht 30% Performance gewinnt bringen
* eventuell die andere Anwendung optimieren um Last von der DB zu nehmen

---

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
