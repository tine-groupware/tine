# Accessibility
## Warum wollen wir die Accessibility für Tine verbessern?
Wir wollen dass Tine von allen Benutzern gleichermaßen genutzt werden kann und verhindern, dass die Web-App für bestimmte Menschen nicht benutzbar ist. Zu Beginn wollen wir uns darauf konzentrieren, dass Menschen mit verschiedenen Sehbeinträchtigungen Tine so gut wie möglich nutzen können. Durch eine verbesserte Accessibility helfen wir nicht nur Menschen mit Sehbeinträchtigung, sondern letztendlich allen Benutzern (durch z.B. größere Schrift, bessere Navigation per Tastatur etc.).

## Tipps zur besseren Bedienbarkeit
- mit der Tab-Taste kann zwischen allen Elementen navigiert werden
- das jeweils fokussierte Element wird dann rot umrandet
- Menüs können mit der Enter-Taste geöffnet und in ihnen mit den Pfeiltasten navigiert werden
- Menüs können immer mit der Escape-Taste geschlossen werden (jedoch geht dabei noch oft der Fokus verloren, nur bei Elementen der rechten, oberen Leiste wird der Fokus beim Schließen des Menüs erfolgreich auf den Button zurückgesetzt)
- Mit einem Screen-Reader werden einem die Label der einzelnen Elemente vorgelesen, auch z.B. bei Buttons die nur ein Symbol anzeigen
- Mit einem Screen-Reader ist auch eine bessere Navigation der Seite möglich, da so durch Tastenkombinationen bestimmte Elemente erreicht werden können (dies ist leider bisher nur eingeschränkt möglich, um z.B. zur nächsten Navigation, Suchleiste oder zum nächsten Link zu springen)
- In der Menüleiste oben rechts kann der Dark-Mode ausgewählt werden, welcher das Erkennen von Elementen und Lesen von Texten vereinfachen kann
- Momentan ist noch keine Veränderung der Schriftgröße möglich, die meisten Betriebssysteme verfügen aber über eine Barrierefreiheits-Einstellung, die alle Schrift vergrößert. Des Weiteren kann auch der Zoom im Browser hilfreich sein, um Elemente besser zu erkennen

## Was bereits ermöglicht wurde
- fokussierte Elemente werden rot umrandet
- die meisten Elemente in sind per Tab erreichbar
- Menüs können mit Enter geöffnet, mit den Pfeiltasten navigiert werden und mit Escape geschlossen werden (jedoch geht der Fokus beim Schließen meistens noch verloren)
- alle fokussierbaren Elemente werden von Screen-Readern vorgelesen
- Tabellen-Zeilen werden als ein Element vorgelesen
- Ansage beim Fokussieren bestimmter Button, die darüber informiert, dass beim Aktivieren des Buttons ein Fenster geöffnet wird
- grundlegende Navigation mit Screen-Reader Tastenkombinationen

## Was wir noch umsetzen wollen
- Navigation mit Tab zu allen relevanten Elementen in allen Apps
- das Schließen aller Menüs per Escape-Taste ermöglichen ohne das der Fokus verloren geht
- Navigation per Tastenkombination verbessern
- Fokus zu Beginn auf Hauptinhalt setzen
- vorgelesene Label für bessere Verständlichkeit ergänzen (z.B. "Kontakt Max Mustermann löschen" statt nur "Kontakt löschen")
- Schriftgröße einstellbar machen
- Modus mit hohem Kontrast erstellen (zusätzlich zu Darkmode und Lightmode)
- angemessen auf den eigenen Kontrastmodus von Benutzern reagieren (verhindern dass der Kontrastmodus blockiert wird und sicherstellen dass fokussierbare Elemente deutlich erkennbar sind)

## Wissenswertes zu Accessibility
Eine Übersicht relevanter Begriffe zum Thema Accessibility für Tine.

### Screen-Reader
Screen-Reader sind Programme, die die Navigation für Menschen mit Sehbeinträchtigungen vereinfachen. Sie lesen fokussierte Elemente vor und stellen Tastenkombinationen zur Verfügung, die eine schnellere Navigation ermöglichen. Dadurch ist es einfach möglich direkt zum Hauptinhalt zu springen, zwischen Menüs zu navigieren oder von einer Überschrift zur nächsten zu springen. Dies setzt allerdings voraus, dass die jeweilige Seite, auch so aufgebaut ist, dass Menüs, Überschriften etc. auch als solche gekennzeichnet sind.

Die meisten Betriebssysteme haben einen eingebauten Screen-Reader, es gibt allerdings auch viele separate Programme, die teilweise bessere Funktionalitäten ermöglichen. Die beliebtesten Optionen sind NVDA und JAWS für Windows und Apple VoiceOver für macOS.

### Kontrastmodus
Ein Kontrastmodus hebt Texte, Symbole und Bedienelemente durch starke Farbgegensätze (z.B. weiße Schrift auf schwarzem Grund) deutlich hervor, um die Lesbarkeit für Menschen mit Sehbeeinträchtigungen zu verbessern. Im Gegensatz zum einfachen "Dark Mode" überschreibt der Kontrastmodus häufig alle Standardfarben, Linien und Rahmen. Grafiken oder Hintergrundbilder via CSS werden oft ausgeblendet.

Die meisten Betriebssysteme haben einen eingebauten Kontrastmodus der in den Systemeinstellungen ausgewählt und teilweise individuell angepasst werden kann. Einige Webseiten bieten auch neben einem Light Mode und Dark Mode einen Kontrastmodus an.