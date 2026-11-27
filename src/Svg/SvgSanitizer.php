<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\Svg;

use enshrined\svgSanitize\Sanitizer;

/**
 * Sanitises SVG markup that is printed inline into the page (e.g. by the inline_svg() Twig function).
 *
 * Uses enshrined/svg-sanitize, the allowlist sanitiser Contao core runs on SVG uploads
 * (FileUpload, DC_Folder), with remote references removed. <style> is allowed, so CSS
 * animations keep working (SMIL <animate> is not), but its rules are scoped to the SVG
 * (see StyleScoper). Ids get a prefix that is unique per output (see SvgIdPrefixer).
 *
 * Unless the SVG declares its accessibility itself (aria-hidden, role, aria-label or
 * aria-labelledby), it gets role="img" and the given label as aria-label (like the alt text
 * of an <img>), or aria-hidden without a label.
 */
class SvgSanitizer
{
    public function sanitize(string $markup, ?string $label = null): ?string
    {
        $svg = $this->parse($markup, $label);

        if (null === $svg) {
            return null;
        }

        return $this->toMarkup($svg);
    }

    /**
     * Returns the sanitised <svg> element, so callers can adjust it before printing it with toMarkup().
     */
    public function parse(string $markup, ?string $label = null): ?\DOMElement
    {
        $sanitizer = new Sanitizer();
        $sanitizer->removeXMLTag(true);
        $sanitizer->removeRemoteReferences(true);

        $clean = $sanitizer->sanitize($markup);

        if (!\is_string($clean) || '' === $clean) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadXML($clean, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $svg = $document->documentElement;

        if (!$loaded || null === $svg || 'svg' !== strtolower($svg->localName)) {
            return null;
        }

        $this->scopeStyles($svg, SvgIdPrefixer::prefix($svg));

        if (
            !$svg->hasAttribute('aria-hidden')
            && !$svg->hasAttribute('role')
            && !$svg->hasAttribute('aria-label')
            && !$svg->hasAttribute('aria-labelledby')
        ) {
            if (null !== $label && '' !== trim($label)) {
                $svg->setAttribute('role', 'img');
                $svg->setAttribute('aria-label', $label);
            } else {
                // decorative, like <img alt="">; screen readers get the text next to it
                $svg->setAttribute('aria-hidden', 'true');
            }
        }

        return $svg;
    }

    /**
     * Only the <svg> element itself, without XML declaration / doctype.
     */
    public function toMarkup(\DOMElement $svg): ?string
    {
        return $svg->ownerDocument?->saveXML($svg) ?: null;
    }

    /**
     * Inline <style> rules apply to the whole page, so they are wrapped in a prelude-less @scope,
     * whose root is the parent of the <style> element. Each <style> is therefore moved to the root <svg>
     * (the position of a <style> element doesn't affect rendering).
     */
    private function scopeStyles(\DOMElement $svg, SvgIdPrefixer $ids): void
    {
        // by local name: an SVG without xmlns has its elements in no namespace
        $styles = iterator_to_array((new \DOMXPath($svg->ownerDocument))->query('//*[local-name()="style"]'));

        $scoper = new StyleScoper();

        foreach ($styles as $style) {
            $css = $scoper->scope($style->textContent, $ids);
            $style->textContent = $css;

            // appended in document order, so the cascade between the styles stays the same
            if ('' === $css) {
                $style->parentNode?->removeChild($style);
            } else {
                $svg->appendChild($style);
            }
        }
    }
}
