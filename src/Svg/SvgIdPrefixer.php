<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\Svg;

/**
 * Inline SVGs share the page's id namespace: exported SVGs often use generic ids ("a", "clip0_1_2")
 * and the same SVG can be printed several times, so url(#a) would resolve to the first match in the
 * page. All ids declared in the SVG get a prefix that is unique per output, and the references to
 * them are rewritten: url(#…) in attributes and CSS, href="#…", aria id lists and #id selectors.
 */
class SvgIdPrefixer
{
    private const ID_LIST_ATTRIBUTES = ['aria-labelledby', 'aria-describedby', 'aria-controls', 'aria-owns', 'aria-flowto', 'aria-details'];

    /**
     * @param array<string, string> $map old id => new id
     */
    private function __construct(private readonly array $map)
    {
    }

    /**
     * Prefixes the ids of the given SVG and the references to them in its attributes. Returns the
     * prefixer, so the CSS of its <style> elements can be rewritten as well (see StyleScoper).
     */
    public static function prefix(\DOMElement $svg): self
    {
        $xpath = new \DOMXPath($svg->ownerDocument);
        $prefix = 'svg-'.bin2hex(random_bytes(4)).'-';
        $map = [];

        foreach ($xpath->query('//@id') as $attribute) {
            $map[$attribute->value] = $prefix.$attribute->value;
        }

        $prefixer = new self($map);

        if (!$map) {
            return $prefixer;
        }

        foreach ($xpath->query('//@*') as $attribute) {
            $name = strtolower($attribute->localName);
            $value = $attribute->value;

            $prefixed = match (true) {
                'id' === $name => $map[$value],
                'href' === $name => $prefixer->reference($value),
                \in_array($name, self::ID_LIST_ATTRIBUTES, true) => $prefixer->idList($value),
                default => $prefixer->urls($value),
            };

            if ($prefixed !== $value) {
                $attribute->value = $prefixed;
            }
        }

        return $prefixer;
    }

    /**
     * url(#id), url('#id') and url("#id") in an attribute value or CSS.
     */
    public function urls(string $value): string
    {
        if (!$this->map || !str_contains($value, 'url(')) {
            return $value;
        }

        return preg_replace_callback(
            '/url\(\s*(["\']?)#([^"\')\s]+)\1\s*\)/i',
            fn (array $m): string => isset($this->map[$m[2]]) ? 'url('.$m[1].'#'.$this->map[$m[2]].$m[1].')' : $m[0],
            $value,
        );
    }

    /**
     * #id in a CSS selector (also inside attribute selectors such as [href="#id"], as href is prefixed too).
     */
    public function selector(string $selector): string
    {
        if (!$this->map || !str_contains($selector, '#')) {
            return $selector;
        }

        return preg_replace_callback(
            '/#(-?[_a-zA-Z\x80-\xff][\w\x80-\xff-]*)/',
            fn (array $m): string => isset($this->map[$m[1]]) ? '#'.$this->map[$m[1]] : $m[0],
            $selector,
        );
    }

    private function reference(string $value): string
    {
        if (!str_starts_with($value, '#') || !isset($this->map[substr($value, 1)])) {
            return $value;
        }

        return '#'.$this->map[substr($value, 1)];
    }

    private function idList(string $value): string
    {
        $ids = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        return implode(' ', array_map(fn (string $id): string => $this->map[$id] ?? $id, $ids));
    }
}
