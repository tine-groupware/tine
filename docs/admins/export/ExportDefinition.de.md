Export Definitions
==================

Export Definitionen steuern den Aufruf und die Ausführung von Exporten.  
Die Konfiguration erfolgt im `xml` Format.

**Beispiel:**  
~~~ xml
<?xml version="1.0" encoding="UTF-8"?>
<config>
<model>Calendar_Model_Event</model>
<name>pfarrnachrichten</name>
<type>export</type>
<plugin>ChurchEdition_Export_Gottesdienstordnung</plugin>
<scope>multi</scope>
<template>tine20:///Filemanager/folders/shared/Vorlagen/Gottesdienstplan/Templates/Vorlage_Pfarrnachrichten.docx</template>
<dateformat>EE dd.MM.</dateformat>
<timeformat>HH:mm</timeformat>
<label>Pfarrnachrichten</label>
<multiDay>daily</multiDay>
<description>Exportiert Pfarrnachrichten</description>
<showCelebrant>0</showCelebrant>
<showIntentions>0</showIntentions>
<showOffertoryPurpose>0</showOffertoryPurpose>
<allInOne>0</allInOne>
<favorite>true</favorite>
<exportFilename>Pfarrnachrichten {{ dateFormat(calendar.from, 'YYYY') }}-{{ dateFormat(calendar.from, 'MM') }}-{{ dateFormat(calendar.from, 'd')+1 }}.docx</exportFilename>
</config>
~~~




