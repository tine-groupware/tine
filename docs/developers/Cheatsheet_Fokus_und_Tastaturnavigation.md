# Cheatsheet: Fokus & Tastaturnavigation für Tine

## 1. Wie Browser-Fokus grundsätzlich funktioniert

- Zu jedem Zeitpunkt gibt es genau **ein** `document.activeElement`.
- Fokus wandert dorthin durch: Tab/Shift+Tab, Mausklick, oder `element.focus()` per JS.
- Screenreader/Tine bieten auch verschidene Tastenkombinationen an, die den Focus auf bestimmte Elemente setzen.
- Fokussierbar per Tab sind: Links, Buttons, Form-Elemente, sowie alles mit `tabindex="0"`.
- `tabindex="-1"`: per Skript fokussierbar (`.focus()`), aber **kein** Tab-Stopp.
- Positive `tabindex`-Werte (1, 2, …) vermeiden – zerstören die natürliche DOM-Reihenfolge.

## 2. Die zwei Fokus-Konzepte in CSS

| | `:focus` | `:focus-visible` |
|---|---|---|
| Matcht bei | **jeder** Fokussierung (Klick, Tab, JS) | nur wenn Ring vermutlich hilfreich ist (v.a. Tastatur) |
| Bei Mausklick auf Button | ja | meist nein |
| Bei Tab-Navigation | ja | ja |
| Empfehlung | nicht für Styling nutzen | **hierauf** den Fokusring stylen |

```css
/* Falsch – entfernt Ring auch für Tastaturnutzer */
button:focus { outline: none; }

/* Richtig */
button:focus-visible {
  outline: 2px solid #005fcc;
  outline-offset: 2px;
}
```

⚠️ Bei eigenen Widgets (Roving Tabindex etc.) den Ring bewusst selbst stylen und testen – Browser-Heuristik bei `element.focus()` ist nicht überall konsistent.

## 3. Was wir für Tine wollen (Grundprinzip)

- **Tab** = springt zwischen **Hauptbereichen/Komponenten** (grob).
- **Pfeiltasten** = navigieren **innerhalb** einer komplexen Komponente (fein).
- Eine komplexe Komponente (Grid, Toolbar, Menü) ist damit **ein einziger Tab-Stopp** – nicht 50.
- Enter/Esc zum Rein-/Rausspringen in Sub-Ebenen nur bei **echten** verschachtelten Composite-Widgets (Menü→Untermenü, Zell-Edit-Modus), **nicht** als globale Regel für alle vier Hauptbereiche.

## 4. Makro-Ebene: Landmarks statt Eigenbau

Für "zwischen Hauptmenü / App-Menü / Toolbar / Main springen" **keine eigene Logik** erfinden – ARIA-Landmarks nutzen:

```html
<header role="banner">…Hauptmenü…</header>
<nav aria-label="App-Menü">…</nav>
<div role="toolbar" aria-label="Werkzeugleiste">…</div>
<main>…</main>
```

- Screenreader haben eingebaute Sprünge zwischen Landmarks (NVDA: `D`, JAWS: `R`, VoiceOver: Rotor).
- Optional zusätzlich eigene Shortcuts (`F6`/`Ctrl+F6` oder `Alt+1..4`) für sehende Tastaturnutzer.
- Jede Landmark braucht ein eindeutiges `aria-label`, wenn es mehrere gleichen Typs gibt (z.B. zwei `nav`).
- Der Hauptinhalt (bei den meisten Tine-Apps die mittlere Tabelle) sollte die Rolle `main` haben.
- Auswahl des richtigen Landmark-Types macht Aufbau für Benutzer mit Sehbeinträchtigungen besser verständlich

- **Mögliche Landmark-Typen:**
    - `main`für den Hauptinhalt
    - `navigation` für die Navigation
    - `banner` für den Header
    - `contentinfo` für den Footer
    - `complementary` für Inhalte die mit dem Hauptinhalt verwandt sind, aber auch eigenständig funktionieren (z.B. Seitenleisten)
    - `search`für Suchfunktionalitäten
    - `region`für einen allgemeinen Bereich, der zu keinem der anderen Landmark-Typen passt

## 5. Mikro-Ebene: Roving Tabindex (Standardmuster für Composite Widgets)

**Ziel:** Ein Grid/Menü/Toolbar ist ein Tab-Stopp, Pfeiltasten bewegen den Fokus innerhalb.

**Regel:**
- Genau **ein** Kind-Element der Gruppe hat `tabindex="0"` (das "aktive" Mitglied).
- Alle anderen Kinder haben `tabindex="-1"`.
- Bei Pfeiltaste wird der `tabindex="0"` von einem Element zum anderen **verschoben** ("roving") und Fokus dorthin gesetzt.

```javascript
function moveFocus(fromEl, toEl) {
  fromEl.setAttribute('tabindex', '-1'); // altes Element raus aus Tab-Reihenfolge
  toEl.setAttribute('tabindex', '0');    // neues Element rein
  toEl.focus();                          // Fokus aktiv dorthin bewegen
}
```

**Warum das funktioniert:**
- Echter DOM-Fokus bewegt sich → `:focus-visible`-Ring erscheint automatisch, kein manuelles Highlighting nötig.
- Tab von außen führt direkt zum aktiven Mitglied (merkt sich Position – nicht immer Element 1).

**Aber:** Roving Tabindex regelt _nur_ das Tastatur-Verhalten (den physischen Fokus), aber es wird **keinerlei Semantik oder Zustand** an den Screenreader (den virtuellen Fokus) übermittelt. Dadurch weiß der Screenreader nicht, was für ein Steuerelement er vor sich hat und was die Interaktion des Nutzers bedeutet.

**Deshalb zusätzlich nötig:**
- Je nach Widget-Rolle: `aria-selected` / `aria-checked` auf dem aktiven Element für korrekte Screenreader-Ansage. Über Attribute wie `aria-selected="true"` (für Tabs) oder `aria-checked="true"` (für Radio-Buttons oder Menüpunkte) erfährt der virtuelle Fokus des Screenreaders die Statusänderung.
- Passende ARIA-Rolle auf dem Container (`role="grid"`, `role="menu"`, `role="toolbar"` …) und den Kindern (`role="gridcell"`, `role="menuitem"` …), damit native Pfeiltastensteuerung möglich wird.

## 6. Alternative zu Roving Tabindex: `aria-activedescendant`

- DOM-Fokus bleibt die ganze Zeit auf dem **Container**, nur ein Attribut ändert sich: `aria-activedescendant="id-des-eintrags"`.
- Screenreader sagen trotzdem den referenzierten Eintrag an ("virtueller Fokus").
- **Nachteil:** Kein automatischer visueller Ring – Highlighting muss manuell per CSS-Klasse nachgebaut werden.
- In der Praxis: **Roving Tabindex bevorzugen**, außer es gibt einen konkreten Grund für `aria-activedescendant` (z.B. sehr große, dynamisch nachladende Listen).

## 7. Kontextwechsel

Ein Kontextwechsel beschreibt eine **spürbare Veränderung in dem, was der Nutzer auf dem Bildschirm sieht oder wie er mit der App interagiert**. Solche Änderungen müssen **vorhersehbar** sein, damit Nutzer nicht die Orientierung verlieren.
Beispiele sind Öffnen eines neuen Fensters, Fokussprünge oder Weiterleitungen.

Best Practices für Entwickler:
- **Keine automatischen Aktionen beim Fokussieren:** Ein Element sollte niemals die Seite wechseln oder ein Pop-up öffnen, nur weil es mit der Tab-Taste ausgewählt wurde.
- **Aktionen nur durch Nutzereingabe:** Änderungen des Kontexts sollten durch das explizite Betätigen einer Schaltfläche (wie einem `Submit`-Button) ausgelöst werden.
- **Warnungen nutzen:** Wenn sich ein Kontextwechsel nicht vermeiden lässt (z. B. das Öffnen eines neuen Fensters), sollte der Nutzer vorab deutlich darüber informiert werden.

## 8. Focus-Trap
Ein Focus Trap ist ein Design-Element, das die **Aufmerksamkeit eines Benutzers in einem bestimmten Bereich einer Webseite hält**. Es verhindert, dass der Bereich durch Tab oder Pfeiltasten verlassen werden kann. Der Bereich soll nur mit Esc verlassen werden können. **Wichtig ist, dass der Benutzer nicht in dem Element gefangen ist**.

**Genutzt werden Fokustraps vorallem bei:**
- Modal oder Dialog
- Dropdown-Menüs
- Kontextmenüs
- Popovers mit Interaktionen
- Lightboxes oder Overlays
- Tabs oder Tab-Interfaces

## 9. Physischer vs. Virtueller Fokus
Der Unterschied zwischen physischem und virtuellem Fokus liegt darin, **welches Element für die Tastatur aktiv ist** im Vergleich dazu, **was ein Screenreader gerade vorliest**.

- **Physischer Fokus (DOM-Fokus / Tastatur-Fokus):**
    - Steuert, welches Element **Tastatureingaben empfängt**.
    - Setzen wir mit `element.focus()`
    - Sichtbar durch Fokus-Rahmen
    - Wo der physische Fokus ist erfahren wir mit `document.activeElement`
    - Fokus, den der Browser nativ verwaltet.
- **Virtueller Fokus (Screenreader-Fokus / Accessibility-Fokus):**
    - "Cursor" eines Screenreaders
    - Bestimmt, **welcher Text oder welches Element gerade vorgelesen wird**.
    - Kann mit Pfeiltasten auch auf Elementen landen, die man mit der `Tab`-Taste gar nicht erreichen kann (wie normalen Text oder Überschriften).

**Das Problem in der Praxis:** Wenn physischer und virtueller Fokus auseinanderlaufen (z. B. bei schlecht programmierten Pop-ups oder Modalen), tippt der Nutzer ins Leere, während der Screenreader etwas völlig anderes vorliest. Beide Fokus-Arten sollten daher für eine gute Barrierefreiheit harmonisch synchronisiert werden.

**Wie man Auseinanderlaufen verhindert:**
- native HTML-Elemente nutzen
- Fokus per JavaScript gezielt setzen
- Fokustraps einrichten
- unsichtbare Inhalte für Screenreader sperren (`aria-hidden`)

## 10. Faustregeln / Don'ts

- ❌ Kein `outline: none` auf `:focus` ohne Ersatz.
- ❌ Kein Enter/Esc-Trap für einfache Bereiche mit normalem Formular-Inhalt – normaler Tab-Fluss reicht.
- ❌ Keine positiven `tabindex`-Werte.
- ❌ `role="application"` nur für echte App-artige Spezialkomponenten (Editor, Canvas) – schaltet den Screenreader-Lesemodus komplett ab, hoher Aufwand/Fehlerquelle.
- ✅ Landmarks für Makro-Navigation, Roving Tabindex für Composite Widgets, `:focus-visible` fürs Styling.
- ✅ Jeder Fokus-Trap muss mit Esc verlassbar sein (WCAG 2.1.2, kein Keyboard-Trap).