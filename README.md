KIWI. Contao CMX-Bundle

- Widget
- Twig Functions
- UI-Actions
- SVG inline ausgeben: Checkbox in den Bildeinstellungen von Text-Elementen, gibt ein SVG-Bild als Markup statt als `<img>` aus (z. B. für CSS-Animationen)

## SVG inline ausgeben (twig only)

Ist bei einem Text-Element „SVG inline ausgeben“ (`tl_content.svgInline`) aktiv und das Bild ein SVG, schreibt das Template `content_element/text.html.twig` das Markup direkt in die Seite.

Das Markup wird bereinigt, `<style>` bleibt erhalten und wirkt nur innerhalb der Grafik. Namen aus `@keyframes` und `@font-face` gelten aber seitenweit, SVGs sollten deshalb eindeutige Namen verwenden (z. B. `check-circle-draw` statt `fadeIn`).

Ohne eigene Accessibility-Angaben (`aria-hidden`, `role`, `aria-label`, `aria-labelledby`) bekommt das SVG den Alternativtext des Bildes als `aria-label` (mit `role="img"`), ohne Alternativtext `aria-hidden="true"`.

Die Checkbox erscheint nur bei unterstützten Elementtypen (Standard: `text`).

### Implementierung in eigene Elemente

Weitere unterstützte Elemente können per DCA-Eintrag hinzugefügt werden:

```php
// contao/dca/tl_content.php
$GLOBALS['TL_DCA']['tl_content']['fields']['svgInline']['showForTypes'][] = 'fact';
```

In eigenen Templates liefert `inline_svg(source, label)` das bereinigte Markup oder `null` (Quelle: binäre UUID, UUID-String oder Pfad unterhalb von `files/`; `label` optional, z. B. der Alternativtext):

```twig
{% set svg = inline_svg(data.singleSRC) %}
{% if svg %}{{ svg }}{% else %}…{% endif %}
```
