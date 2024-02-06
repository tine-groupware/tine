<?php
/**
 * Tinebase Twig class
 *
 * @package     Tinebase
 * @subpackage  Twig
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase Twig class
 *
 * @package     Tinebase
 * @subpackage  Twig
 *
 */
class Tinebase_Twig
{
    const TWIG_AUTOESCAPE = 'autoEscape';
    const TWIG_LOADER = 'loader';
    const TWIG_CACHE = 'cache';

    /**
     * @var Twig_Environment
     */
    protected $_twigEnvironment = null;

    /**
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_translate;

    /**
     * locale object
     *
     * @var Zend_Locale
     */
    protected $_locale;

    public function __construct(Zend_Locale $_locale, Zend_Translate $_translate, array $_options = [])
    {
        $this->_locale = $_locale;
        $this->_translate = $_translate;

        if (isset($_options[self::TWIG_LOADER])) {
            $twigLoader = $_options[self::TWIG_LOADER];
        } else {
            $twigLoader = new Twig\Loader\FilesystemLoader(['./'], dirname(__DIR__));
        }

        if (!defined('TINE20_BUILDTYPE') || TINE20_BUILDTYPE === 'DEVELOPMENT'
            || (isset($_options[self::TWIG_CACHE]) && !$_options[self::TWIG_CACHE])
        ) {
            $cacheDir = false;
        } else {
            $cacheDir = rtrim(Tinebase_Core::getCacheDir(), '/') . '/tine20Twig';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
        }

        $options = [
            'cache' => $cacheDir ? new Tinebase_Twig_ImprovedFileCache($cacheDir) : $cacheDir,
        ];

        if (isset($_options[self::TWIG_AUTOESCAPE])) {
            $options['autoescape'] = $_options[self::TWIG_AUTOESCAPE];
        }
        $this->_twigEnvironment = new Twig\Environment($twigLoader, $options);
        
        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection PhpUnusedParameterInspection */
        $this->_twigEnvironment->getExtension(Twig\Extension\EscaperExtension::class)->setEscaper('json', function($twigEnv, $string, $charset) {
            return json_encode($string);
        });

        $this->_twigEnvironment->addExtension(new Twig_Extensions_Extension_Intl());

        $this->_addTwigFunctions();

        $this->_addGlobals();
    }

    protected function _addGlobals()
    {
        $tbConfig = Tinebase_Config::getInstance();

        $globals = [
            'websiteUrl'        => $tbConfig->{Tinebase_Config::WEBSITE_URL},
            'branding'          => [
                'logo'              => Tinebase_Core::getInstallLogo(),
                'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
                'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
                'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
            ],
            'user'              => [
                'locale'            => Tinebase_Core::getLocale(),
                'timezone'          => Tinebase_Core::getUserTimezone(),
            ],
            'currencySymbol'    => $tbConfig->{Tinebase_Config::CURRENCY_SYMBOL},
        ];
        $this->_twigEnvironment->addGlobal('app', $globals);
    }

    /**
     * @param string $_filename
     * @return Twig_TemplateWrapper
     */
    public function load($_filename)
    {
        return $this->_twigEnvironment->load($_filename);
    }

    /**
     * @param Twig_LoaderInterface $loader
     */
    public function addLoader(Twig_LoaderInterface $loader)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->_twigEnvironment->getLoader()->addLoader($loader);
    }

    /**
     * @return Twig_Environment
     */
    public function getEnvironment()
    {
        return $this->_twigEnvironment;
    }

    /**
     * adds twig function to the twig environment to be used in the templates
     */
    protected function _addTwigFunctions()
    {
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('config', function ($key, $app='') {
            $config = Tinebase_Config::getInstance();
            if ($app) {
                $config = $config->{$app};
            }
            return $config->{$key};
        }));

        $locale = $this->_locale;
        $translate = $this->_translate;

        $n = new NumberFormatter($this->_locale->toString(), NumberFormatter::DECIMAL);
        /** @var \Twig\Extension\CoreExtension $extension */
        $extension = $this->_twigEnvironment->getExtension(\Twig\Extension\CoreExtension::class);
        $extension->setNumberFormat(0,
            $n->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
            $n->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL));

        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('removeSpace', function($str) {
            return str_replace(' ', '', (string)$str);
        }));
        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('transliterate', function($str) {
            return Tinebase_Helper::replaceSpecialChars($str, false);
            // much better would be iconv or transliterator
            // iconv('UTF-8', 'ASCII//TRANSLIT', iconv(mb_detect_encoding($str), 'UTF-8//IGNORE', $str));
            // this iconv works very well on the glibc implementation, sadly docker php versions may come with
            // a different iconv implementation: Interactive shell
            // php > echo ICONV_IMPL;
            // unknown
            // => that implementation transliterates ö to "o instead of oe
            // transliterator_transliterate('de-ASCII', $str); does not transliterate € sign, also I have trouble with
            // that 'de' in there... probably the transliterator would be the best option, but we would need to find
            // the right way to use it, feel free to improve
        }));

        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('preg_replace', function($subject, $pattern, $replacement, int $limit=-1, int $count=null) {
            return preg_replace($pattern, $replacement, $subject, $limit, $count);
        }));

        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('translate',
            function ($str) use($locale, $translate) {
                $translatedStr = $translate->translate($str, $locale);
                if ($translatedStr == $str) {
                    $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $locale)->translate($str, $locale);
                }

                return $translatedStr;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('_',
            function ($str) use($locale, $translate) {
                $translatedStr = $translate->translate($str, $locale);
                if ($translatedStr == $str) {
                    $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $locale)->translate($str, $locale);
                }

                return $translatedStr;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('ngettext',
            function ($singular, $plural, $number) use($locale, $translate) {
                $translatedStr =  $translate->plural($singular, $plural, $number, $locale);
                if (in_array($translatedStr, [$singular, $plural])) {
                    $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $locale)->plural($singular, $plural, $number, $locale);
                }

                return $translatedStr;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('addNewLine',
            function ($str) {
                return (is_scalar($str) && strlen((string)$str) > 0) ? $str . "\n" : $str;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('dateFormat', function ($date, $format) {
            if (!($date instanceof DateTime)) {
                $date = new Tinebase_DateTime($date, Tinebase_Core::getUserTimezone());
            }
            
            return Tinebase_Translation::dateToStringInTzAndLocaleFormat($date, null, null, $format);
        }));

        $staticData = [];
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('setStaticData', function ($key, $data) use(&$staticData) {
            $staticData[$key] = $data;
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('getStaticData', function ($key) use(&$staticData) {
            return isset($staticData[$key]) ? ($staticData[$key] ?: null) : null;
        }));

        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('relationTranslateModel', function ($model) {
            if (!$model || !class_exists($model)) return $model;
            return $model::getRecordName();
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('keyField', function ($appName, $keyFieldName, $key, $locale = null) {
            $config = Tinebase_Config::getAppConfig($appName)->$keyFieldName;
            $keyFieldRecord = ($config && $config->records instanceof Tinebase_Record_RecordSet && is_string($key))
                ? $config->records->getById($key)
                : false;

            if ($locale !== null) {
                $locale = Tinebase_Translation::getLocale($locale);
            }
            
            $translation = Tinebase_Translation::getTranslation($appName, $locale);
            return $keyFieldRecord ? $translation->translate($keyFieldRecord->value) : $key;
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('renderTags', function ($tags) {
            if (!($tags instanceof Tinebase_Record_RecordSet)) {
                return '';   
            }
            
            return implode(', ', $tags->getTitle());
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('findBySubProperty',
            function ($records, $property, $subProperty, $value) {
                return $records instanceof Tinebase_Record_RecordSet ?
                    $records->find(function($record) use($property, $subProperty, $value) {
                        return $record->{$property} instanceof Tinebase_Record_Interface &&
                            $record->{$property}->{$subProperty} === $value;
                }, null) : null;
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('filterBySubProperty',
            function ($records, $property, $subProperty, $value) {
                return $records instanceof Tinebase_Record_RecordSet ?
                    $records->filter(function($record) use ($property, $subProperty, $value) {
                        return $record->{$property} instanceof Tinebase_Record_Interface &&
                            $record->{$property}->{$subProperty} === $value;
                    }, null) : null;
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('formatMessage',
            function(string $msg, array $data) use($locale, $translate) {
                return msgfmt_format_message((string)$locale, $translate->translate($msg, $locale), $data);
            }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('getCountryByCode',
            function($code) use($locale) {
                return Tinebase_Translation::getCountryNameByRegionCode($code, $locale) ?: $code;
            }));
    }

    public function addExtension(Twig_ExtensionInterface $extension)
    {
        $this->_twigEnvironment->addExtension($extension);
    }
}
