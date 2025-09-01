<?php declare(strict_types=1);

/**
 * Tinebase Twig Template Loader
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/* * @noinspection PhpDeprecationInspection */
class Tinebase_Twig_TineLoader implements Twig\Loader\SourceContextLoaderInterface
{
    public static ?Zend_Locale $locale = null;

    /** @var array<Tinebase_Model_TwigTemplate> */
    protected static array $cache = [];

    protected function getTwigTemplate($name): ?Tinebase_Model_TwigTemplate
    {
        return Tinebase_Twig::getTemplateContent($name, (string)(static::$locale ?? Tinebase_Core::getLocale()));
    }
    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists($name)
    {
        return (bool)(static::$cache[$name] ?? (static::$cache[$name] = $this->getTwigTemplate($name)));
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @return \Twig\Source
     *
     * @throws Twig\Error\LoaderError When $name is not found
     */
    public function getSourceContext($name)
    {
        if (!(static::$cache[$name] ?? (static::$cache[$name] = $this->getTwigTemplate($name)))) {
            throw new Twig\Error\LoaderError($name . ' not found');
        }
        return new \Twig\Source(static::$cache[$name]->{Tinebase_Model_TwigTemplate::FLD_TWIG_TEMPLATE}, $name);
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name string The name of the template to load
     *
     * @return string The cache key
     * @throws Twig\Error\LoaderError When $name is not found
     */
    function getCacheKey($name)
    {
        if (!(static::$cache[$name] ?? (static::$cache[$name] = $this->getTwigTemplate($name)))) {
            throw new Twig\Error\LoaderError($name . ' not found');
        }
        return $name . (static::$cache[$name]->last_modified_time ?: static::$cache[$name]->creation_time)->getTimestamp();
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int $time The last modification time of the cached template
     *
     * @return bool
     * @throws Twig\Error\LoaderError When $name is not found
     */
    function isFresh($name, $time)
    {
        if (!(static::$cache[$name] ?? (static::$cache[$name] = $this->getTwigTemplate($name)))) {
            throw new Twig\Error\LoaderError($name . ' not found');
        }
        return $time > (static::$cache[$name]->last_modified_time ?: static::$cache[$name]->creation_time)->getTimestamp();
    }
}
