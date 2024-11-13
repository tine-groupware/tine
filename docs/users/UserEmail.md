Tine User Schulung: Felamimail
=================

Version: 2024.11

Problemlösungen im Felamimail-Modul von tine

HowTo: Suchen aller Nachrichten suchen, die dem Glob Filter entsprechen

### Wo man den Glob Filter einstellt:

1. klicken Sie unter dem Schnellsuchfilter auf "Details anzeigen"
2. Erstellen Sie einen Ordner-Filter im Filter panel
3. Klicken Sie auf das Suchsymbol
4. Öffnen Sie den Ordnerbaum und wählen Sie einen Ordner aus.
5. Klicken Sie auf den vorhandenen Ordnerpfad und bearbeiten Sie ihn zum Glob Filter
6. klicken Sie auf OK

=================

### Hier ist ein Beispiel für die IMAP Ordnerstruktur

```
KONTO1
 |- INBOX
 |-- 1
 |--- 2
      
KONTO2
 |- INBOX
 |-- 1
 |--- 2
```

### Beispiele für Glob Filtersuche

| Glob Filter        | Suche im Konto | Suche in Ordnern                   | Suchergebnis                                                                                                        |
|--------------------|:--------------:|------------------------------------|---------------------------------------------------------------------------------------------------------------------|
| /**                |  Alle Konten   | Unterordner (rekursiv)             | KONTO1.INBOX<br/>KONTO1.INBOX.1<br/>KONTO1.INBOX.1.2<br/>KONTO2.INBOX<br/>KONTO2.INBOX.1<br/>KONTO2.INBOX.1.2 |
| /\*/INBOX          |  Alle Konten   | INBOX                              | KONTO1.INBOX<br/>KONTO2.INBOX                                                                                     |
| /\*/\*             |  Alle Konten   | Unterordner                        | KONTO1.INBOX<br/>KONTO2.INBOX                                                                                     |                                                                                     
| /\*/INBOX/\*\*     |  Alle Konten   | Unterordner (rekursiv) unter INBOX | KONTO1.INBOX.1<br/>KONTO1.INBOX.1.2<br/>KONTO2.INBOX.1<br/>KONTO2.INBOX.1.2                                     |                                                                                     
| /KONTO1/**        |    KONTO1     | Unterordner (rekursiv)             | KONTO1.INBOX<br/>KONTO1.INBOX.1<br/>KONTO1.INBOX.1.2                                                             |                                                                                         
| /KONTO1/*         |    KONTO1     | Unterordner                        | KONTO1.INBOX                                                                                                       |                                                                                       
| /KONTO1/INBOX/*   |    KONTO1     | Unterordner unter INBOX            | KONTO1.INBOX.1                                                                                                     |                                                                                       
| /KONTO1/INBOX/1/* |    KONTO1     | Unterordner unter 1                | KONTO1.INBOX.1.2                                                                                                   |                                                                                       
| /KONTO1/INBOX/**  |    KONTO1     | Unterordner (rekursiv) unter INBOX | KONTO1.INBOX.1<br/>KONTO1.INBOX.1.2                                                                               |                                                                                       
| /KONTO1/INBOX/1   |    KONTO1     | 1                                  | KONTO1.INBOX.1                                                                                                     |                                                                                       

### [Glob Filter Tester](https://toools.cloud/miscellaneous/glob-tester)
- Derzeit unterstützen wir die "!" Syntax in unserer Glob Filter nicht.
