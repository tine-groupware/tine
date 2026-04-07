<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */
class Tinebase_Frontend_Http_CspRegistry
{

    use Tinebase_Controller_SingletonTrait;
    protected static array $_sources = [
        'script-src'  => [],
        'connect-src' => [],
        'img-src'     => [],
        'frame-src'   => [],
        'style-src'   => [],
        'object-src'  => [],
    ];

    public function addSource(string $directive, string $source): void
    {
        if (!array_key_exists($directive, self::$_sources)) {
            throw new Tinebase_Exception_InvalidArgument(
                "Unknown CSP directive: $directive"
            );
        }

        if (!in_array($source, self::$_sources[$directive], true)) {
            self::$_sources[$directive][] = $source;
        }
    }

    public function getSources(string $directive): array
    {
        return self::$_sources[$directive] ?? [];
    }

    public function reset(): void
    {
        array_walk(self::$_sources, fn(&$v) => $v = []);
    }
}
