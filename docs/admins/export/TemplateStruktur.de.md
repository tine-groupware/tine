Template Struktur
=================

Die Template Struktur und der Aufbau ist abhängig von den Dateiformaten `xlsx` und `docx`.  
Templates werden im Filemanager oder auf dem Webdav abgelegt.  
In der `ExportDefinition` erfolgt der Aufruf des Templates unter Angabe des Pfades.  

> *Hinweis:*  
> Bei der Erstellung von Templates geht der Editor schnell kaputt und generiert defekte Templates. Im Template ist dies nicht erkennbar! Die Ausführung des Export erzeugt einen Error. Bisherige Arbeiten am Template sind erneut durchzuführen bzw. alle Arbeiten ab der letzten funktionalen Version. Es empfiehlt sich step by step vorzugehen und die Arbeiten wie folgt zu iterieren: anpassen, hochladen, ausführen.  
> Es ist weiterhin darauf zu achten, dass keine unnötigen Zeichen im Template vor der Ausgabe des Records stehen. DIes führt ebenso oftmals zu Fehler oder defekten Templates.  
> Weiterhin empfiehlt sich bei der Erstellung von Templates kein LibreOffice einzusetzen, speziell wenn das Ergebnis des Export mit MSO oder OO erfolgt. Hier kommt es oft zu ungewollten Formatierungen.

Blöcke
---
Grundsätzlich werden Export Templates in `Blöcke` unterteilt, welche je nach Dateiformat unterschiedlich sind.  

**Eigenschaften**  

- Blöcke können ineinander verschachtelt werden  
- jeder Block besteht aus einem `Start`und einem `Ende`  
- Blocktitel werden in Grossbuchstaben angegeben  
- innerhalb von `Start`und `Ende` erfolgt die Ausgabe der erforderlichen Daten, Bilder, Steuerelemente etc.  
- ein Template Prozessor kopiert den Block und ersetzt diesen mit Inhalt  
- jeder Block wird mindestens einmal oder pro gefundenem Datensatz ausgegeben  

**Syntax**  

[Start] => `${BLOCK}`  
[Ende] => `${/BLOCK}`  

Dateiformat xlsx
---

**Record Block**  

Die Ausgabe pro Record erfolgt in Zeilen mit dem Befehl `ROWS`.  
Dabei wird der `Start` des Blockes in der ersten Zelle vorangestellt und in der letzten Zelle mit dem `Ende` des Blocks geendet.  

Die Ausgabe von Records innerhalb von Gruppen erfolgt mit dem Befehl `GROUPSTART` in Kombination mit dem Befehl `ROWS`.  
Dabei wird der `GROUPSTART`Block in einer Zeile vor der Ausgabe der `ROWS`Zeile mit den auszugebenden Gruppendaten gesetzt.

***Beispiel - Records mit Ergebnis 3 Datensätze:***
~~~
${ROWS}{{record.email_adr}} | {{record.last_name}} | {{record.first_name}}${/ROWS}
~~~
Ergebnis:
~~~
mustermann@firma.de | Mustermann | Hans  
musterfrau@firma.de | Musterfrau | Erika  
test@firma.de       | Test       | Testi  
~~~

***Beispiel - Records nach Gruppen mit Ergebnis 3 Datensätze in 2 Gruppen:***
~~~
${GROUPSTART}{{record.group_title}}${/GROUPSTART}
${ROWS}{{record.email_adr}} | {{record.last_name}} | {{record.first_name}}${/ROWS}
~~~
Ergebnis:
~~~
Gruppe 1
mustermann@firma.de | Mustermann | Hans  
musterfrau@firma.de | Musterfrau | Erika  
Gruppe 2
test@firma.de       | Test       | Testi  
~~~

Dateiformat docx
---

**Record Block**  

Die Ausgabe pro Record erfolgt als Block innerhalb der Befehle `Start` und `Ende`.
Innerhalb des Blocks können diverse Daten ausgegeben werden.  

Soll z.B. zwischen jedem Record ein Linie zur Trennung, ist ein `RECORD_SEPARATOR`Block erforderlich.

***Beispiel - Records mit Ergebnis 3 Datensätze:***
~~~
${RECORD_BLOCK}
{{record.email_adr}}
{{record.first_name}} {{record.last_name}}

${/RECORD_BLOCK}
~~~
Ergebnis:
~~~
mustermann@firma.de
Hans Mustermann

musterfrau@firma.de
Erika Musterfrau

test@firma.de
Testi Test
~~~

***Beispiel - Trennlinie zwischen Records mit Ergebnis 3 Datensätze:***
~~~
${RECORD_BLOCK}
{{record.email_adr}}
{{record.first_name}} {{record.last_name}}
${/RECORD_BLOCK}
${RECORD_SEPARATOR}
__________________________________________________________________________________
${/RECORD_SEPARATOR}

Text: Hier erfolgt die weitere Ausgabe.
~~~
Ergebnis:
~~~
mustermann@firma.de
Hans Mustermann
__________________________________________________________________________________
musterfrau@firma.de
Erika Musterfrau
__________________________________________________________________________________
test@firma.de
Testi Test
__________________________________________________________________________________

Text: Hier erfolgt die weitere Ausgabe.
~~~

**Datasource Block**  

Mit Hilfe von `DATASOURCE`Blöcken, können diverse Daten für den Export aufbereitet werden.  
Somit wird ermöglicht z.B. Daten vorzufiltern oder ganz bestimmte Daten für den Export zusammenzustellen. 
Hierfür wird pro `DATASOURCE` ein eigenes PlugIn implementiert, welches in der `Export Definition` aufgerufen wird.  
`DATASOURCE`Blöcke erhalten einen `Namenszusatz` und haben einen `Start` und ein `Ende`.
In einem Template können mehrere `DATASOURCE`Blöcke ausgeführt werden.  
Innerhalb eines `DATASOURCE`Block können diverse weitere Blöcke (`Group`, `Record`,`Separator`) etc. und Daten ausgegeben werden.

> *Hinweis:*  
> Erfolgt innerhalb einer `DATASOURCE` die Ausgabe der Daten mit Hilfe einer Tabelle, ist jede Zeile ein Datensatz (Record).  
> Ohne Tabellenausgabe wird ein `RECORD_BLOCK` innerhalb der `DATASOURCE` benötigt.

***Beispiel - Records einer speziellen Datasource nach Gruppen inkl. Trennlinie mit Ergebnis 3 Datensätze in 2 Gruppen:***  

- die `DATASOURCE_cal` enthält z.B. alle Freizeit-Kalender  
- der `GROUP_BLOCK`Block gruppiert Kalender-übergreifend nach Datum und sortiert die Termine nach Zeit  
- `exportgroupdata` gibt das Datum einmalig pro Gruppe aus  
- pro `RECORD_>BLOCK`innerhalb von `exportgroupdata` werden die Termindaten ausgegeben  
- nach Ausgabe einer `GROUP_BLOCK` (Gruppenwechsel = Datum) erfolgt die Ausgabe eines `GROUP_SEPARATOR`in Form einer Linie  

~~~
${DATASOURCE_cal}
${GROUP_BLOCK}
++ {{exportgroupdata}} ++
${RECORD_BLOCK} 
{{record.dtstart.format('H:i')}} - {{record.summary}}, {{record.location}}
${/RECORD_BLOCK}
${/GROUP_BLOCK}
${GROUP_SEPARATOR}
__________________________________________________________________________________
${/GROUP_SEPARATOR}
${/DATASOURCE_cal}
~~~
Ergebnis:
~~~
++ 01.01.2024 ++
10:00 - Yoga, Kiel
11:00 - Kinderchor, Laboe
__________________________________________________________________________________
++ 02.01.2024 ++
10:00 - Gospel, Kiel
__________________________________________________________________________________
~~~
