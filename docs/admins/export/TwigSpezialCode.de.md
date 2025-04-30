Spezieller tine Twig-Code:
====

Farbsteuerung in Abhängigkeit von Dateninhalten
----
Die Farbsteuerung kann z.B. in einzelnen Zellen von Tabellen genutzt werden.  
Insbesondere, wenn die Farbgebung, in Abhängigkeit von bestimmten Dateninhalten erfolgen soll.

`POSTP_TC_FILL_D3D3D3~!§`  
`POSTP_TC_FILL_auto~!§`

> *Hinweis:*  
> Die Farbe `REINWEISS = FFFFFF` wird nicht bzw. nicht bei inneren Tabellen (Tabelle in Zelle einer äußeren Tabelle) über den Farbbefehl in der Ausgabe beachtet.
> Hier muss die innere Tabelle über Formatierung Rahmen/Schatten/Hintergünde von Transparent auf `WEISS` gestellt werden.

***Beispiel Ausgabe bestimmter Wochentage in bestimmter Farbe:***  
~~~
{%if record.dtstart.format('w') == 0%}POSTP_TC_FILL_ffbb99~!§{%endif%}
{%if (record.dtstart.format('w') == 0 or record.dtstart.format('w') == 6)%}POSTP_TC_FILL_D3D3D3~!§{%endif%}
~~~

***Beispiel unterschiedliche Farben je nach Wochentag:***  
~~~
{%if record.dtstart.format('w') == 0 %} POSTP_TC_FILL_D3D3D3~!§ {%else%} POSTP_TC_FILL_D3D3D3~!§ {%endif%}
~~~

***Beispiel Farbe des Tabellenhintergrundes ausgeben:***  
~~~
{%if (record.dtstart.format('w') == 0 or record.dtstart.format('w') == 6)%}POSTP_TC_FILL_auto~!§{%endif%}
~~~

**Ausblick**

> *Hinweis:*  
> ! Option noch nicht vorhanden (Stand 03/2024)
> Voraussetzung ist ein Feiertagskalender, welcher in der Definition angegeben werden kann.

***Beispiel Feiertage mit Farb-Hintergrund ausgeben:***  
~~~
{%if isFeastDay(record.dtstart) %}POSTP_TC_FILL_D3D3D3~!§{%endif%}
~~~



