Tine 2.0 Admin Schulung: Filemanager
=================

Version: Nele 2018.11

Konfiguration und Problemlösungen des Filemanager-Moduls von Tine 2.0

Volltextindizierung mit Apache Tika
=================

Damit die Datei-Inhalte im Filemanager durchsucht werden können, muss Apache Tika (https://tika.apache.org/ - tika-app.jar)
 auf dem Server verfügbar sein. Das ist eine Jar-Datei, die an einem konfigurierten
 Ort hinterlegt sein muss. Außerdem muss dafür eine Java-Runtime installiert sein:
 Das Debian Standard jre (headless) ist ausreichend (z.B. https://packages.debian.org/stretch/openjdk-8-jre-headless).

Getestet wurde die Funktionalität mit Version 1.14, es sollte aber auch mit neueren Versionen klappen.

Die Konfiguration in Tine 2.0 sieht dann so aus (z.B. config.inc.php):

    'fulltext' => array(
        'tikaJar' => '/usr/share/tika.jar',
        [...]
    ),
    'filesystem => array(
        'index_content' => true,
        [...]
    ), 

Beim Speichern einer Datei wird diese dann indiziert.

Um den Index nachträglich mit den vorhandenen Dateien zu füllen, muss dieses Kommando aufgerufen werden:

    $ tine20-cli --method=Tinebase.fileSystemCheckIndexing

Wenn es sofort beendet ist stimmt die Konfig nicht,  es sollte relativ lange dauern. Und auch die Logs cheken,
 im Fehlerfall tauchen dann dort Infos auf.

Wenn alles klappt, sollte sowas im (DEBUG-)Log stehen:

    Tinebase_Fulltext_TextExtract::fileObjectToTempFile::100 tika success!

(100 ist die zeilennummer in der php datei, kann sich natürlich mit der zeit ändern ...)

Liste mit den Benutzern mit den meisten Daten im Tine 2.0 VFS (Virtual File System) erstellen
=====

    sql> select user.login_name,fo.created_by, sum(fr.size) as filesize from tine20_tree_fileobjects as fo JOIN tine20_tree_filerevisions as fr ON fo.id = fr.id join tine20_accounts as user on user.id=fo.created_by group by fo.created_by order by filesize DESC;


