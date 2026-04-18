<?php

declare(strict_types=1);

namespace Nativephp\AllPermissionHandler\Support;

use DOMDocument;
use DOMElement;

/**
 * Merges string entries into an Apple XML .plist (document root is plist, first child dict).
 * Used so Laravel config-driven NS*UsageDescription keys reach real Xcode Info.plists.
 */
class IosInfoPlistMerger
{
    /**
     * @param  array<string, string>  $stringEntries  plist key => string value
     */
    public static function mergeFile(string $plistPath, array $stringEntries): bool
    {
        if ($stringEntries === []) {
            return true;
        }

        if (! is_file($plistPath) || ! is_readable($plistPath)) {
            return false;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (@$dom->load($plistPath) === false) {
            return false;
        }

        $dict = self::rootDict($dom);
        if (! $dict instanceof DOMElement) {
            return false;
        }

        foreach ($stringEntries as $keyName => $value) {
            if (! is_string($keyName) || ! is_string($value)) {
                continue;
            }
            if ($keyName === '') {
                continue;
            }

            self::mergeOneStringEntry($dom, $dict, $keyName, $value);
        }

        $xml = $dom->saveXML();
        if (! is_string($xml)) {
            return false;
        }

        return file_put_contents($plistPath, $xml) !== false;
    }

    private static function rootDict(DOMDocument $dom): ?DOMElement
    {
        $root = $dom->documentElement;
        if (! $root instanceof DOMElement || strtolower($root->tagName) !== 'plist') {
            return null;
        }

        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'dict') {
                return $child;
            }
        }

        return null;
    }

    private static function mergeOneStringEntry(DOMDocument $dom, DOMElement $dict, string $keyName, string $value): void
    {
        $existingKey = self::findKeyElement($dict, $keyName);

        if ($existingKey instanceof DOMElement) {
            $valueEl = self::nextElementSibling($existingKey);
            if ($valueEl instanceof DOMElement && strtolower($valueEl->tagName) === 'string') {
                self::setElementTextContent($valueEl, $value);

                return;
            }

            if ($valueEl instanceof DOMElement) {
                $stringEl = $dom->createElement('string');
                self::setElementTextContent($stringEl, $value);
                $dict->replaceChild($stringEl, $valueEl);

                return;
            }

            $stringEl = $dom->createElement('string');
            self::setElementTextContent($stringEl, $value);
            $dict->insertBefore($stringEl, $existingKey->nextSibling);

            return;
        }

        $keyEl = $dom->createElement('key');
        self::setElementTextContent($keyEl, $keyName);
        $stringEl = $dom->createElement('string');
        self::setElementTextContent($stringEl, $value);
        $dict->appendChild($keyEl);
        $dict->appendChild($stringEl);
    }

    private static function findKeyElement(DOMElement $dict, string $keyName): ?DOMElement
    {
        foreach ($dict->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            if (strtolower($child->tagName) === 'key' && $child->textContent === $keyName) {
                return $child;
            }
        }

        return null;
    }

    private static function nextElementSibling(DOMElement $node): ?DOMElement
    {
        $next = $node->nextSibling;
        while ($next !== null && ! $next instanceof DOMElement) {
            $next = $next->nextSibling;
        }

        return $next instanceof DOMElement ? $next : null;
    }

    private static function setElementTextContent(DOMElement $element, string $text): void
    {
        while ($element->firstChild !== null) {
            $element->removeChild($element->firstChild);
        }
        $element->appendChild($element->ownerDocument->createTextNode($text));
    }
}
