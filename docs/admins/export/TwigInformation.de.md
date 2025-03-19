Twig Informationen
====

Twig Dokumentation
----
In Text und Tabellenkalkulations-Vorlagen können Platzhalter verwendet werden die beim Export durch entsprechende Werte ersetzt werden.
Um die Platzhalter zu ersetzen wird [Twig](https://twig.symfony.com/doc/) verwendet.

Beispiel: \

    Der Termin startet um {{record.dtstart.format('H:i')}} Uhr. 
    Wir treffen uns hier: {{record.location}}


!!! note "Alte Syntax"
In alten exports sind die Twig Platzhalter in eine weitere Ebene von internen Platzhaltern eingebettet. Die äußeren Klammern (`{{` und `}}`) entfallen dann

    Der Termin startet um ${twig:record.dtstart.format('H:i')} Uhr.
    Wir treffen uns hier: ${twig:record.location}

Innerhalb von Twig kann auf die Felder des Datensatzes mit ihren internen Feldnamen zugegriffen werden. 

Ist der Wert eines Feldes ein einfacher Text oder eine Zahl kann diese ohne Weiterverarbeitung sofort ausgegeben werden.

Für andere Datentypen muss jedoch spezifiziert werden wie diese ausgegeben werden sollen. Hierzu können Twig Funktionen und Filter (siehe unten) verwendet werden.

Werte von Feldern können Objekte wie Datum und Uhrzeit (`Tinebase_DateTime`) Datensätze (`Tinebase_Record_Abstract`) oder Listen von Datensätzen (`Tinebase_Record_RecordSet`) sein. In diesem Fall kann auf die Eigenschaften und Funktionen der Objekte direkt mit einem `.` dereferenziert werden.




Funktionen
---
Neben den im Twig Standard enthaltenen [Funktionen](https://twig.symfony.com/doc/2.x/functions/index.html) sind die Folgenden speziellen Funktionen verfügbar:

* `config(key, app)` gibt den entsprechenden Konfigurationswert zurück.
* `removeSpace(text)`
* `transliterate(text)`
* `accountLoginChars(text)`
* `preg_replace(...)` siehe https://www.php.net/preg_replace
* `translate(text)` Übersetzt den gegebenen Text in der Sprache des Nutzendens (oder je nachdem des Datensatzes)
* `_` alias für `translate(...)`
* `ngettext(sigular, plural, number)` Übersetzt die gegebene plurale Form
* `addNewLine(text)` Fügt einen Zeilenumbruch ein, wenn der übergebene Wert ein nicht leerer Text ist
* `dateFormat(date, format)` Übersetzt und formatiert das gegebene datums Objekt in die Sprache des Nutzenden (oder je nachdem des Datensatzes). Wenn sie das Format der Zielsprache möchten geben sie als Format `'date'`, `'time'` oder `'datetime` an. Ansonten können sie das Format frei mit [ISO Format Codes](https://examples.mashupguide.net/lib/ZendFramework-0.9.3-Beta/documentation/end-user/core/de/zend.date.constants.html#zend.date.constants.selfdefinedformats) bestimmen.
* `uvm...` @TODO


Filter
---
Neben den im Twig Standard enthaltenen [Filtern](https://twig.symfony.com/doc/2.x/filters/index.html) sind die Folgenden speziellen Filter verfügbar:
@TODO

Erweiterungen
---
Derzeit werden im Standard die folgenden Extensions geladen:

    $this->_twigEnvironment->addExtension(new Twig_Extensions_Extension_Intl());
    $this->_twigEnvironment->addExtension(new CssInlinerExtension());
