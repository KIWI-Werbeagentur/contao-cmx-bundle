<?php

declare(strict_types=1);

namespace Kiwi\Contao\CmxBundle\Twig;

use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\Validator;
use Kiwi\Contao\CmxBundle\Svg\SvgSanitizer;
use Symfony\Component\Uid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * inline_svg(source, label): returns the markup of an SVG file from the files directory, so it
 * can be printed inline (e.g. to animate it with CSS). Returns null if the source is not
 * an SVG or cannot be read (also for a stale UUID of a deleted file), so templates can
 * fall back to the regular <img>.
 *
 * The source can be a binary UUID (as stored in tl_content.singleSRC), a UUID string or
 * a path relative to the files directory. The optional label (e.g. the image's alt text) is the
 * fallback accessible name: it is only used if the SVG declares no accessibility attributes of
 * its own (aria-hidden, role, aria-label, aria-labelledby). Without either, the SVG is decorative
 * (see SvgSanitizer).
 *
 * Inline SVG is part of the page, so the markup is sanitised (see SvgSanitizer). It is returned
 * as Twig\Markup, so it is not escaped, also when stored in a variable first.
 */
class InlineSvgExtension extends AbstractExtension
{
    public function __construct(
        private readonly VirtualFilesystemInterface $files,
        private readonly SvgSanitizer $sanitizer,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('inline_svg', $this->inlineSvg(...)),
        ];
    }

    public function inlineSvg(mixed $source, ?string $label = null): ?Markup
    {
        $location = $this->toLocation($source);

        if (null === $location) {
            return null;
        }

        try {
            $item = $this->files->get($location);

            if (null === $item || !$item->isFile() || 'svg' !== strtolower($item->getExtension())) {
                return null;
            }

            $markup = $this->files->read($location);
        } catch (VirtualFilesystemException|UnableToResolveUuidException) {
            return null;
        }

        $markup = $this->sanitizer->sanitize($markup, $label);

        return null === $markup ? null : new Markup($markup, 'UTF-8');
    }

    private function toLocation(mixed $source): Uuid|string|null
    {
        if (!\is_string($source) || '' === $source) {
            return null;
        }

        // binary UUID from the database
        if (Validator::isBinaryUuid($source)) {
            return Uuid::fromBinary($source);
        }

        if (Uuid::isValid($source)) {
            return Uuid::fromString($source);
        }

        // path, with or without the leading "files/"
        return preg_replace('#^files/#', '', $source);
    }
}
