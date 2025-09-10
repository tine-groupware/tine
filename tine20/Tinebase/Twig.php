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

use Twig\Extra\CssInliner\CssInlinerExtension;
use Twig\Extra\Html\HtmlExtension;


/**
 * Tinebase Twig class
 *
 * @package     Tinebase
 * @subpackage  Twig
 *
 */
class Tinebase_Twig
{
    public const TWIG_AUTOESCAPE = 'autoEscape';
    public const TWIG_LOADER = 'loader';
    public const TWIG_CACHE = 'cache';

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
            $twigLoader = new Twig\Loader\ChainLoader([
                new Tinebase_Twig_TineLoader(),
                new Twig\Loader\FilesystemLoader(['./'], dirname(__DIR__)),
            ]);
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
        $this->_twigEnvironment->getExtension(Twig\Extension\EscaperExtension::class)->setEscaper('json', fn($twigEnv, $string, $charset) => json_encode($string));

        $this->_twigEnvironment->addExtension(new Twig_Extensions_Extension_Intl());
        $this->_twigEnvironment->addExtension(new CssInlinerExtension());
        $this->_twigEnvironment->addExtension(new HtmlExtension());
        $this->_twigEnvironment->addExtension(new Tinebase_Twig_BootstrapEmailExtension());


        $this->_addTwigFunctions();

        $this->_addGlobals();
    }

    protected function _addGlobals()
    {
        $tbConfig = Tinebase_Config::getInstance();

        $account = Tinebase_Core::getUser();
        $contact = $account instanceof Tinebase_Model_User ? Addressbook_Controller_Contact::getInstance()->getContactByUserId(
            $account->getId(),
            true) : null;

        $globals = [
            Addressbook_Config::INSTALLATION_REPRESENTATIVE => Addressbook_Config::getInstallationRepresentative(),
            'websiteUrl'        => $tbConfig->{Tinebase_Config::WEBSITE_URL},
            'branding'          => [
                'logo'              => Tinebase_Core::getInstallLogo(),
                'logoContent'       => Tinebase_Controller::getInstance()->getLogo(),
                'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
                'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
                'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
            ],
            'user'              => [
                'account'           => $account,
                'contact'           => $contact,
                'locale'            => Tinebase_Core::getLocale(),
                'timezone'          => Tinebase_Core::getUserTimezone(),
            ],
            'currencySymbol'    => Tinebase_Core::getDefaultCurrencySymbol(),
        ];
        $this->_twigEnvironment->addGlobal('app', $globals);
    }

    public static function getTemplateContent(string $path, string $locale): ?Tinebase_Model_TwigTemplate
    {
        $path = ltrim($path, '/');
        $filename = basename($path);
        $baseDir = dirname($path);
        $tineRoot = dirname(__DIR__) . '/';

        $localeParts = explode('_', $locale);
        $language = $localeParts[0] ?? '';

        // de_DE
        // first check de_DE dir and if nothing is found check de dir fallback to given path else
        // Check paths in order of specificity:
        // 1. Full locale (e.g., de_DE/file.txt)
        // 2. Language only (e.g., de/file.txt)
        // 3. Original path (baseDir/file.txt)
        $possiblePaths = array_merge(
            [$baseDir . '/' . $locale],
            $language && $language !== $locale ? [$baseDir . '/' . $language] : [],
            [$baseDir]
        );
        // Return the first existing path
        foreach ($possiblePaths as $possiblePath) {
            $pathToTest = $possiblePath . '/' . $filename;
            if ($twigTmpl = Tinebase_Controller_TwigTemplate::getInstance()->getByPath($pathToTest, skipAcl: true)) {
                return $twigTmpl;
            } elseif (file_exists($tineRoot . $pathToTest)) {
                return new Tinebase_Model_TwigTemplate([
                    Tinebase_Model_TwigTemplate::FLD_PATH => $pathToTest,
                    Tinebase_Model_TwigTemplate::FLD_TWIG_TEMPLATE => file_get_contents($tineRoot . $pathToTest),
                    'last_modified_time' => ($mtime = filemtime($tineRoot . $pathToTest)) ? new Tinebase_DateTime($mtime) : Tinebase_DateTime::now()->subSecond(1),
                ], true);
            }
        }
        return null;
    }

    /**
     * @param string $_filename
     * @param Zend_Locale $locale
     *
     *  directory structure
     *    some.twig          <-- default (en), taken if nothing else matches
     *    de_DE/some.twig    <-- exact match for de_DE
     *    de/some.twig       <-- matches de_AT for example
     * @return Twig\TemplateWrapper
     */
    public function load($_filename, ?\Zend_Locale $locale = null)
    {
        Tinebase_Twig_TineLoader::$locale = $locale;

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
            // $app is not using the factory properly, this always results in TB Config.
            // we do not fix this! This function is deprecated and should be removed and not be used at all
            $config = Tinebase_Config::getInstance();
            if ($app) {
                if (! ($config->getProperties()[$app]['clientRegistryInclude'] ?? false)) {
                    throw new Tinebase_Exception($app . ' doesn\'t have clientRegistryInclude set, not allowed!');
                }
                if (! ($config->getProperties()[$app][$key]['clientRegistryInclude'] ?? false)) {
                    throw new Tinebase_Exception($app . '.' . $key .' doesn\'t have clientRegistryInclude set, not allowed!');
                }
                $config = $config->{$app};
            } else {
                if (! ($config->getProperties()[$key]['clientRegistryInclude'] ?? false)) {
                    throw new Tinebase_Exception($key . ' doesn\'t have clientRegistryInclude set, not allowed!');
                }
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

        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('removeSpace', fn($str) => str_replace(' ', '', (string)$str)));
        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('transliterate', fn($str) => iconv('UTF-8', 'ASCII//TRANSLIT', transliterator_transliterate('de-ASCII', (string) $str))));
        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('toRomanNumber', fn($str) => (new NumberFormatter('@numbers=roman', NumberFormatter::DECIMAL))->format(intval($str))));
        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('accountLoginChars', fn($str) => preg_replace('/[^\w\-_.@\d+]/u', '', $str)));
        $this->_twigEnvironment->addFilter(new Twig_SimpleFilter('preg_replace', fn($subject, $pattern, $replacement, int $limit=-1, ?int $count=null) => preg_replace($pattern, $replacement, $subject, $limit, $count)));

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
            fn($str) => (is_scalar($str) && strlen((string)$str) > 0) ? $str . "\n" : $str));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('dateFormat', function ($date, $format) {
            if (!($date instanceof DateTime)) {
                $date = new Tinebase_DateTime($date, Tinebase_Core::getUserTimezone());
            }
            return Tinebase_Translation::dateToStringInTzAndLocaleFormat($date, null, $this->_locale, $format);
        }));
        $this->_twigEnvironment->addFunction(new \Twig\TwigFunction('getSize', function ($byte, $force_unit = null) {
            $result = Tinebase_Helper::formatBytes($byte, $force_unit);
            return $result;
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
        $this->_twigEnvironment->addFunction(new \Twig\TwigFunction('renderModel', fn($modelName) => $modelName::getConfiguration()->recordName));
        $this->_twigEnvironment->addFunction(new \Twig\TwigFunction('renderTitle', function ($record, $modelName) {
            if (! $record instanceof Tinebase_Record_Abstract) {
                $record = new $modelName($record);
            }
            return $record->getTitle();
        }));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('findBySubProperty',
            fn($records, $property, $subProperty, $value) => $records instanceof Tinebase_Record_RecordSet ?
                $records->find(fn($record) => $record->{$property} instanceof Tinebase_Record_Interface &&
                    $record->{$property}->{$subProperty} === $value, null) : null));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('filterBySubProperty',
            fn($records, $property, $subProperty, $value) => $records instanceof Tinebase_Record_RecordSet ?
                $records->filter(fn($record) => $record->{$property} instanceof Tinebase_Record_Interface &&
                    $record->{$property}->{$subProperty} === $value, null) : null));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('formatMessage',
            fn(string $msg, array $data) => msgfmt_format_message((string)$locale, $translate->translate($msg, $locale), $data)));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('getCountryByCode',
            fn($code) => Tinebase_Translation::getCountryNameByRegionCode($code, $locale) ?: $code));
        $this->_twigEnvironment->addFunction(new Twig_SimpleFunction('sanitizeFileName',
            function($string) {
                return Tinebase_Model_Tree_Node::sanitizeName($string);
            }));
        $this->_twigEnvironment->addFunction(new \Twig\TwigFunction('localizeString', function ($records, $locale = null) {
            $language = is_string($locale) ? $locale : $locale->getLanguage();
            $record = $records?->find(Tinebase_Record_PropertyLocalization::FLD_LANGUAGE, $language);

            if (!$record) {
                $record = $records?->find(Tinebase_Record_PropertyLocalization::FLD_LANGUAGE, 'en');
            }

            return $record ? $record->{Tinebase_Record_PropertyLocalization::FLD_TEXT} : '';
        }));
        $this->_twigEnvironment->addFunction(new \Twig\TwigFunction('getMimeIconCls', function ($node) {
            // Map MIME types to icon filenames
            $nodeType = $node['type'];
            if ($nodeType === 'folder') {
                return "mime-icon-folder";
            } else {
                $mimeType = $node['contenttype'];
                return 'mime-content-type-' . preg_replace('/\/.*$/', '', $mimeType) .
                    ' mime-suffix-' . (preg_match('/\+/', $mimeType) ? preg_replace('/^.*\+/', '', $mimeType) : 'none') .
                    ' mime-type-' . str_replace(
                        ['/g', '.', '+'],
                        ['-slash-', '-dot-', '-plus-'],
                        $mimeType
                    );
            }
        }));
    }

    public function addExtension(Twig_ExtensionInterface $extension)
    {
        $this->_twigEnvironment->addExtension($extension);
    }
}
