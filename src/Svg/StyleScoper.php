<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\Svg;

/**
 * Scopes the CSS of an inline SVG <style> element to the SVG, so it cannot restyle the page.
 *
 * The rules are wrapped in a prelude-less @scope (scope root: the parent of the <style> element),
 * and every selector becomes ":where(:scope, :scope *):is(<selector>)". The explicit :scope keeps
 * the selector from being made relative to the root, so selectors still match the root <svg>
 * itself and may refer to ancestors outside the SVG (e.g. ".mod_article--inview .ring"), while
 * the matched element has to be inside the SVG. Specificity is unchanged (:where() adds none).
 *
 * Browsers without @scope support drop the rules: the graphic stays unstyled, the page is safe.
 */
class StyleScoper
{
    /**
     * At-rules whose blocks contain style rules (their selectors are scoped as well).
     * Other block at-rules (@keyframes, @font-face, …) are kept as they are.
     */
    private const GROUPING_RULES = ['media', 'supports', 'container', 'layer', 'scope', 'starting-style', 'document'];

    public function scope(string $css): string
    {
        $css = trim($this->stripComments($css));

        if ('' === $css) {
            return '';
        }

        return "@scope {\n".$this->scopeRules($css)."\n}";
    }

    private function scopeRules(string $css): string
    {
        $result = [];
        $length = \strlen($css);
        $offset = 0;

        while ($offset < $length) {
            $end = $this->findAtDepthZero($css, $offset, ['{', ';']);

            if (null === $end) {
                break;
            }

            $prelude = trim(substr($css, $offset, $end - $offset));

            // at-rule statement, e.g. @import (invalid inside @scope, so the browser ignores it)
            if (';' === $css[$end]) {
                $result[] = $prelude.';';
                $offset = $end + 1;

                continue;
            }

            $close = $this->findClosingBrace($css, $end);
            $block = substr($css, $end + 1, $close - $end - 1);
            $offset = $close + 1;

            if (str_starts_with($prelude, '@')) {
                preg_match('/^@([\w-]+)/', $prelude, $matches);
                $name = strtolower($matches[1] ?? '');

                if (\in_array($name, self::GROUPING_RULES, true)) {
                    $block = $this->scopeRules($block);
                }

                $result[] = $prelude.' {'.$block.'}';

                continue;
            }

            $result[] = $this->scopeSelectorList($prelude).' {'.$block.'}';
        }

        return implode("\n", $result);
    }

    private function scopeSelectorList(string $selectorList): string
    {
        $selectors = [];
        $offset = 0;
        $length = \strlen($selectorList);

        while ($offset <= $length) {
            $end = $this->findAtDepthZero($selectorList, $offset, [',']) ?? $length;
            $selector = trim(substr($selectorList, $offset, $end - $offset));

            if ('' !== $selector) {
                $selectors[] = ':where(:scope, :scope *):is('.$selector.')';
            }

            $offset = $end + 1;
        }

        return implode(', ', $selectors);
    }

    /**
     * Position of the first of the given characters outside of strings, parentheses, brackets and braces.
     */
    private function findAtDepthZero(string $css, int $offset, array $characters): ?int
    {
        $depth = 0;
        $length = \strlen($css);

        for ($i = $offset; $i < $length; ++$i) {
            $char = $css[$i];

            if ('"' === $char || "'" === $char) {
                $i = $this->skipString($css, $i);
            } elseif ('\\' === $char) {
                ++$i;
            } elseif (0 === $depth && \in_array($char, $characters, true)) {
                return $i;
            } elseif ('(' === $char || '[' === $char || '{' === $char) {
                ++$depth;
            } elseif (')' === $char || ']' === $char || '}' === $char) {
                --$depth;
            }
        }

        return null;
    }

    private function findClosingBrace(string $css, int $open): int
    {
        return $this->findAtDepthZero($css, $open + 1, ['}']) ?? \strlen($css);
    }

    /**
     * Position of the closing quote of the string starting at $start.
     */
    private function skipString(string $css, int $start): int
    {
        $quote = $css[$start];
        $length = \strlen($css);

        for ($i = $start + 1; $i < $length; ++$i) {
            if ('\\' === $css[$i]) {
                ++$i;
            } elseif ($quote === $css[$i]) {
                return $i;
            }
        }

        return $length;
    }

    private function stripComments(string $css): string
    {
        $result = '';
        $length = \strlen($css);

        for ($i = 0; $i < $length; ++$i) {
            $char = $css[$i];

            if ('"' === $char || "'" === $char) {
                $end = $this->skipString($css, $i);
                $result .= substr($css, $i, $end - $i + 1);
                $i = $end;
            } elseif ('/' === $char && '*' === ($css[$i + 1] ?? '')) {
                $end = strpos($css, '*/', $i + 2);
                $i = false === $end ? $length : $end + 1;
            } else {
                $result .= $char;
            }
        }

        return $result;
    }
}
