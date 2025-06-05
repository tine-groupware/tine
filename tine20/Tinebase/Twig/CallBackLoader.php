<?php
/**
 * Tinebase Twig Template Loader
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * Tinebase Twig Template Loader
 *
 * @package     Tinebase
 * @subpackage  Twig
 */
/** @noinspection PhpDeprecationInspection */
class Tinebase_Twig_CallBackLoader implements Twig\Loader\LoaderInterface, Twig\Loader\SourceContextLoaderInterface, Twig\Loader\ExistsLoaderInterface
{
    /**
     * @var callable|null
     */
    protected $_callBack = null;

    /**
     * Tinebase_Twig_Loader constructor.
     * @param string   $_name
     * @param int $_creationTimeStamp
     * @param callable $_callBack
     */
    public function __construct(protected $_name, protected $_creationTimeStamp, $_callBack)
    {
        $this->_callBack = $_callBack;
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
        return $name === $this->_name || str_starts_with($name, $this->_name . '#~#');
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @return Twig_Source
     *
     * @throws Twig_Error_Loader When $name is not found
     */
    public function getSourceContext($name)
    {
        $str = call_user_func($this->_callBack);

        return new Twig_Source($str, $name);
    }

    /**
     * Gets the source code of a template, given its name.
     *
     * @param  string $name string The name of the template to load
     *
     * @return string The template source code
     *
     * @deprecated since 1.27 (to be removed in 2.0), implement Twig_SourceContextLoaderInterface
     */
    function getSource($name)
    {
        return call_user_func($this->_callBack);
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param  string $name string The name of the template to load
     *
     * @throws Twig_Error_Loader
     * @return string The cache key
     */
    function getCacheKey($name)
    {
        if ($name !== $this->_name && !str_starts_with($name, $this->_name . '#~#')) {
            throw new Twig_Error_Loader('template ' . $name . ' not found');
        }
        return $name . $this->_creationTimeStamp;
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string    $name The template name
     * @param int       $time The last modification time of the cached template
     *
     * @throws Twig_Error_Loader
     * @return bool
     */
    function isFresh($name, $time)
    {
        if ($name !== $this->_name && !str_starts_with($name, $this->_name . '#~#')) {
            throw new Twig_Error_Loader('template ' . $name . ' not found');
        }
        return $time > $this->_creationTimeStamp;
    }
}