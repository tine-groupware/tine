Tine 2.0 Admin Schulung: Filemanager / Filesystem
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

Konfiguration eines Preview-Service
=====

Damit der Docservice von Tine 2.0 verwendet wird, muss folgende Konfiguration in die config.inc.php
 hinzugefügt werden:

    'filesystem' => array(
        // [...] andere Filesystem settings
        
        'createPreviews' => true,
        'previewServiceUrl' => 'http://PREVIEWSERVICE/v2/documentPreviewService',
        'previewServiceVersion' => 2,
        'previewMaxFileSize' => 10485760, // 10 MB
    ),

PREVIEWSERVICE = IP-Adresse oder Hostname des Docservice Hosts.

'previewMaxFileSize' ist optional.

Previews werden via Scheduler für alle Dokumente, die noch kein Preview haben, erzeugt
 (läuft, glaube ich, 1x in der Nacht). Für neue Dokumente wird direkt nach dem Hochladen
 die Preview-Generierung angestossen.

Prüfen, ob der PREVIEW-SERVICE funktioniert:

    PREV_URL=https://previewservice.domain
    echo "This is a ASCII text, used to test the document-preview-service." > test.txt
    res=$(curl -F config="{\"test\": {\"firstPage\":true,\"filetype\":\"jpg\",\"x\":100,\"y\":100,\"color\":false}}" -F "file=@test.txt" $PREV_URL/v2/documentPreviewService)
    sha=$(echo $res  | sha256sum)
    if [ "$sha" != "df8f8891a6d892777b010c89288841301bcc72c00779797a189ea5866becad75  -" ]; then
      echo "FAILED"
      exit 1
    fi

Preview-Status anzeigen:

    tine20-cli --method Tinebase.reportPreviewStatus
    
    Array
    (
        [missing] => 0
        [created] => 14
    )

Alle Previews neu erzeugen

- Preview failcount zurücksetzen (soll später auch automatisch passieren: https://taiga.metaways.net/project/admin-tine20-service/us/3069)
```
    MariaDB [tine20]> update tine20_tree_filerevisions set preview_error_count = 0;
```
- Neugenerierung anstossen

```
    tine20-cli --method Tinebase.fileSystemRecreateAllPreviews
```

Wie oft wird der TempFile-Ordner automatisch aufgeräumt?
=====

Der Cleanup-Job Tinebase_TempFileCleanup läuft stündlich und räumt alle (nicht-punkt) Dateien im Temp-Ordner weg, die
 älter als 6 Stunden sind.

Mein TempFile-Ordner ist sehr groß. Kann ich einen Cleanup von Hand anstossen?
=====

Ja, das geht so (Löscht alle Dateien, die älter als 2019-12-19 11:28:00 sind):

    tine20.php --method=Tinebase.clearTable temp_files -- date='2019-12-19 11:28:00'

Änderungen im Dateimanager/Filesystem rückgängig machen z.b. gelöschte Datei wiederherstellen (UNDO-Funktion)
=================

ACHTUNG: damit das im Dateimanager klappt, muss das Filesystem-Modlog angeschaltet sein.

Es wird ein Zugriff auf die Tine 2.0 CLI vorausgesetzt.

Wenn man weiss, von wem und wann Änderungen gemacht wurden, können diese einfach wiederhergestellt
 werden (-d steht für Dry Run):
 
    $ tine20-cli --method=Tinebase.undo -d -- \
      record_type=Tinebase_Model_Tree_Node \
      modification_time=2020-02-17 \
      modification_account=ACCOUNTID
