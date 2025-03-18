<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <c.weiss@metaways.de>
 */

class Tinebase_Config_MCKeyFieldRecord extends Tinebase_Record_NewAbstract
{
    public const FLD_VALUE = 'value';
    public const FLD_ICON = 'icon';
    public const FLD_COLOR = 'color';
    public const FLD_SYSTEM = 'system';
    public const FLD_DISABLED = 'disabled';

    public const MODEL_NAME_PART = 'MCKeyFieldRecord';

    protected static $_modelConfiguration = [
        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,
        self::RECORD_NAME   => 'Key Field Record', // // gettext('GENDER_Key Field Record')
        self::RECORDS_NAME  => 'Key Field Records',// ngettext('Key Field Record', 'Key Field Records', n)
        self::EXPOSE_JSON_API => false,
        self::EXPOSE_HTTP_API => false,
        self::HAS_XPROPS    => true,
//        self::HAS_ALARMS    => false,
//        self::HAS_NOTES     => false,

        self::TITLE_PROPERTY  => self::FLD_VALUE,

        self::FIELDS        => [
            self::FLD_VALUE      => [
                self::LABEL         => 'Value', // _('Value')
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_ICON => [
                self::LABEL         => 'Icon', // _('Icon')
                self::TYPE          => self::TYPE_STRING,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE      => true,
                self::LENGTH        => 255,
            ],
            self::FLD_COLOR => [
                self::LABEL         => 'Color', // _('Color')
                self::TYPE          => self::TYPE_STRING,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE      => true,
                self::LENGTH        => 7,
            ],
            self::FLD_SYSTEM => [
                self::LABEL         => 'System', // _('System')
                self::TYPE          => self::TYPE_BOOLEAN,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL   => 0,
            ],
            self::FLD_DISABLED => [
                self::LABEL         => 'Disabled', // _('Disabled')
                self::TYPE          => self::TYPE_BOOLEAN,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL   => 0,
            ],
        ],
    ];

    public static function getTranslatedValue ($appName, $keyFieldName, $key, $locale = null)
    {
        $config = Tinebase_Config::getAppConfig($appName)->$keyFieldName;
        $keyFieldRecord = $config && $config->records instanceof Tinebase_Record_RecordSet ? $config->records->getById($key) : false;

        if ($locale !== null) {
            $locale = Tinebase_Translation::getLocale($locale);
        }

        $translation = Tinebase_Translation::getTranslation($appName, $locale);
        return $keyFieldRecord ? $translation->translate($keyFieldRecord->value) : $key;
    }

    /** @var Zend_Translate */
    protected static $translation;
    public static function setTranslation(Zend_Translate $translation)
    {
        static::$translation = $translation;
    }

    public function __toString(): string
    {
        return (string) (static::$translation ? static::$translation->getAdapter()->_($this->value) : $this->value);
    }

    public function getTitle()
    {
        return $this->value;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}