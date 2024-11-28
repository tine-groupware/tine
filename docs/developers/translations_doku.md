translations doku
-----------------
Überblick
* Im Quellcode gehen die englischen strings durch die übersetzungsfunktionen `_('en text')`, `ngettext('singular en text', 'plural en text', n)`, `formatMessage('...')` (js only), `translate('...)` (templates)
* Mittels `langHelper -u` werden diese strings aus den sourcen extrahiert und in die po/pot files aufgenommen
* Die strings sollten nicht per Hand in die po files zugefügt werden. Lieber `langHelper -u --app Calendar -l de` für eine app/sprache
* Übersetzen am besten mit dem programm poedit, weil dass auch die Metadaten richtig anpasst
* Die Übersetzungen können auch online getätigt werden. Entwickler mit git nutzen aber lieber poedit und committen/pushen ins git
* Per Default haben unsere po Files keine Zeilennummern, um die Änderungen im Git nicht immer so groß zu haben. Will mensch zum Übersetzen sie haben:
  * `langHelper -u -l de --keep-line-numbers` (Zeilennummern zufügen)
  * übersetzen mit poedit
  * `langHelper -u -l de` (Zeilennummern wieder entfernen)


Was wird übersetzt?
* Alle Apps aus `tine-groupware` übersetzen wir:
  * Die erste hälfte des Releases `current-stable`
  * Die zweite hälfte `next` bzw `main`
* Spezial-Apps zu spezial Editionen werden aus dem Editions-Branch übersetzte / zu tx gepushed
* Siehe `./tx/branches` Konfiguration

prerequisites

    $ sudo apt install php-xml
    $ sudo pip install transifex-client

for po-merge-helper (ubuntu)

    $ sudo apt install gettext

po-merge-helper aktivieren (eintrag in .git/config)

    [merge "pofile"]
        name = merge po-files driver
        driver = ./scripts/git/merge-po-files %A %O %B
        recursive = binary

txmerge - synced übersetzungen mit transifex

(vor am besten neuen branch auschecken)

    $ ./langHelper.php --txmerge --app Calendar -v -l bg

git add

    $ git add */translations

lokale (bzw. DE) deutsche übersetzungen wiederherstellen
TODO: evtl bekommen wir die de translations auch gemerged?

    $ git reset HEAD */translations/de.po 
    $ tx push -t -l de

eventuell muss man force pushen, da online eine "neuere" version ist

    $ tx push -t --force -l de  

commit + push to git

    $ git commit -m "updates translations"
    $ git add */translations
    $ git commit -m "backup de translations"    

