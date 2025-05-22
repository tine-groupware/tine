Twig Informationen
====

Twig Dokumentation
----
In Text und Tabellenkalkulations-Vorlagen können Platzhalter verwendet werden die beim Export durch entsprechende Werte ersetzt werden.
Um die Platzhalter zu ersetzen wird [Twig](https://twig.symfony.com/doc/) verwendet.

Innerhalb von Twig kann auf die Felder eines Datensatzes mit ihren internen Feldnamen zugegriffen werden wobei einzelne Datensätze im Kontext als `record` geführt werden.

Beispiel:

    Für den Termin {{ record.summary }} treffen wir uns hier: {{ record.location }}. 
    Wir starten um: {{ dateFormat(record.dtstart, 'time') }} Uhr. Bitte sei pünklich!


!!! note "Alte Syntax"

    In älteren (legacy) exports sind die Twig Platzhalter in eine weitere Ebene von internen Platzhaltern eingebettet. Die äußeren Klammern (`{{` und `}}`) entfallen dann

        Für den Termin ${twig:record.summary} treffen wir uns hier: ${twig:record.location}. 
        Wir starten um: ${twig:dateFormat(record.dtstart, 'time')} Uhr. Bitte sei pünklich!


Ist der Wert eines Feldes ein einfacher Text oder eine Zahl kann diese ohne Weiterverarbeitung sofort ausgegeben werden.

Für andere Datentypen muss jedoch spezifiziert werden wie diese ausgegeben werden sollen. Hierzu können Twig Funktionen und Filter (siehe unten) verwendet werden.

Werte von Feldern können Objekte wie Datum und Uhrzeit (`Tinebase_DateTime`) Datensätze (`Tinebase_Record_Abstract`) oder Listen von Datensätzen (`Tinebase_Record_RecordSet`) sein. In diesem Fall kann auf die Eigenschaften und Funktionen der Objekte direkt mit einem `.` dereferenziert werden.

Funktionen
---
Neben den im Twig Standard enthaltenen [Funktionen](https://twig.symfony.com/doc/2.x/functions/index.html) sind folgende spezielle Funktionen verfügbar:  
*Wir versuchen diese Dokumentation aktuell zu halten, alternativ sind weitere Infos unter https://github.com/tine-groupware/tine/blob/main/tine20/Tinebase/Twig.php#L140) zu finden.*

* `addNewLine($str)` fügt einen Zeilenumbruch ein, wenn der übergebene Wert ein nicht leerer Text ist.

    Beispiel:  
    ~~~
    ${twig:addNewLine(record.adr_two_street)}${twig:addNewLine(record.adr_two_street2)}${twig:record.adr_two_postalcode}${twig:addNewLine(record.adr_two_locality)}${twig:addNewLine(record.adr_two_region)}${twig:addNewLine(record.adr_two_countryname)}
    ~~~

       Hinweis:  
       > Wird im Template für die Ausgabe einzelner (Adress)Daten ein `Umbruch = [Enter]` oder ein sogenannter `weicher Umbruch = [Enter] + [Shift]` gesetzt, wird bei leerem String eine Leerzeile ausgegeben.

* `config($key, $app='')` gibt den entsprechenden Konfigurationswert zurück.

* `dateFormat($date, $format)` übersetzt und formatiert das gegebene Datums-Objekt in die Sprache des Nutzenden (oder - je nachdem - des Datensatzes). Wenn das Format der Zielsprache gewünscht ist, sind die Formate `'date'`, `'time'` oder `'datetime` anzugeben. Ansonsten kann das Format frei mit [ISO Format Codes](https://examples.mashupguide.net/lib/ZendFramework-0.9.3-Beta/documentation/end-user/core/de/zend.date.constants.html#zend.date.constants.selfdefinedformats) bestimmt werden.

    Beispiele:  
    ~~~
    {{ dateFormat(date, 'date') }}  

    {{dateFormat(date,'dd MMMM YYYY') }}  
    
    {{dateFormat(export.groupdata, 'EEEE dd. MMMM YYYY')}}  
    ~~~

* `filterBySubProperty($records, $property, $subProperty, $value)` 

* `findBySubProperty($records, $property, $subProperty, $value)` 

* `formatMessage(string $msg, array $data)` übersetzt Texte, bei denen Variablen enthalten sind.

    Beispiele:  
    ~~~
    {%if (record.getConfiguration().recordName == 'Invoice')%}{{ formatMessage('Payable within {days} days without deducation.', {'days': record.credit_term}) }} {%endif%}
    
    {{ formatMessage('plus { tax }% tax', {'tax': record.id}) }}
    ~~~

* `getCountryByCod($code)` 

* `getStaticData($key)` gibt eine Variable aus - siehe auch `setStaticData($key, $data)`.

    Beispiel:  
    ~~~
    {{getStaticData('var_n')}}
    ~~~

* `keyField($appName, $keyFieldName, $key, $locale = null)` gibt Schlüsselfelder mit den richtigen Namen aus.

    Beispiel:  
    ~~~
    ${twig:keyField('Calendar', 'eventStatus', record.status)}
    ~~~

* `ngettext($singular, $plural, $number)` übersetzt die gegebene plurale Form.

* `relationTranslateModel($model)` 

* `renderModel($modelName)` 

* `renderTitle($record, $modelName)` 

* `sanitizeFileName($string)` 

* `setStaticData($key, $data)` setzt eine Variable mit gewünschtem Inhalt - siehe auch `getStaticData($key)`.

    Beispiele:  
    ~~~
    {{setStaticData('var_n',record.document_number)}}

    {% if record.document_number%}{{setStaticData('var_number',record.document_number)}}{% else %}{{setStaticData('var_number',record.proformaNumber)}}{% endif %}

    {{setStaticData('var_date',dateFormat(record.date, 'dd.MM.YYYY'))}}
    ~~~

* `translate($str)` übersetzt den gegebenen Text in die Sprache des Nutzenden (oder je nachdem des Datensatzes).

    Beispiel:  
    ~~~
    {{ translate('Customer Number')}}
    ~~~

       Hinweis:  
       > bei manchen Text-Formatierungen in einem Template, wie Ausgabe in GROSSBUCHSTABEN, muss die Formatierung in den Befehl gesetzt werden
       {{record.getConfiguration().recordName |upper}}

* `_($str)` ist ein alias für `translate($str)`.

Filter
---
Neben den im Twig Standard enthaltenen [Filtern](https://twig.symfony.com/doc/2.x/filters/index.html) sind folgende spezielle Filter verfügbar: 

* `accountLoginChars($str)` filtert Login-Zeichen eines Account.

* `preg_replace($subject, $pattern, $replacement, $limit, $count)` sucht und ersetzt mit regulären Ausdrücken, siehe https://www.php.net/preg_replace.

* `removeSpace($str)` filtert Leerzeichen aus einem String.

* `transliterate($str)` filtert bestimmte Zeichen eines Strings und wandelt diese in andere Zeichen/String um (z.B. UTF8).

Erweiterungen
---
Derzeit werden im Standard die folgenden Extensions geladen:

    $this->_twigEnvironment->addExtension(new Twig_Extensions_Extension_Intl());
    $this->_twigEnvironment->addExtension(new CssInlinerExtension());
