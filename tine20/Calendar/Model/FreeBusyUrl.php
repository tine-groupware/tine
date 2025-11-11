<?php declare(strict_types=1);
/**
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Calendar_Model_FreeBusyUrl extends Tinebase_Record_NewAbstract
{
    public const TABLE_NAME = 'cal_free_busy_url';
    public const MODEL_NAME_PART = 'FreeBusyUrl';

    public const FLD_URL = 'url';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_OWNER_ID = 'owner_id';
    public const FLD_OWNER_CLASS = 'owner_class';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::MODLOG_ACTIVE             => true,
        self::IS_DEPENDENT              => true,

        self::APP_NAME                  => Calendar_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'FreeBusy Url', // gettext('GENDER_FreeBusy Url')
        self::RECORDS_NAME              => 'FreeBusy Urls', // ngettext('FreeBusy Url', 'FreeBusy Urls', n)

        self::TITLE_PROPERTY            => self::FLD_URL,
        self::EXPOSE_JSON_API           => true,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_OWNER_ID => [],
            ],
        ],

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::FIELDS                    => [
            self::FLD_URL                   => [
                self::TYPE                      => self::TYPE_STRING,
                self::DOCTRINE_IGNORE           => true,
            ],
            self::FLD_DESCRIPTION           => [
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
            ],
            self::FLD_OWNER_CLASS           => [
                self::TYPE                      => self::TYPE_MODEL,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS          => [
                        Tinebase_Model_User::class,
                        Calendar_Model_Resource::class,
                    ],
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        Tinebase_Model_User::class,
                        Calendar_Model_Resource::class,
                    ]],
                ],
            ],
            self::FLD_OWNER_ID              => [
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::LENGTH                    => 40,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_OWNER_CLASS,
                    self::PERSISTENT                => Tinebase_Model_Converter_DynamicRecord::REFID,
                    self::IS_PARENT                 => true,
                ],
                self::FILTER_DEFINITION             => [
                    self::FILTER                        => Tinebase_Model_Filter_Id::class,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function setId($_id): Tinebase_Record_NewAbstract
    {
        parent::setId($_id);
        $this->setUrl($this->getId());
        return $this;
    }

    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        if (isset($_data['id'])) {
            $this->setUrl($this->getId());
        }
    }

    protected function setUrl(string $id): void
    {
        $this->{self::FLD_URL} = rtrim(Tinebase_Core::getUrl(), '/') . '/Calendar/freebusy/' . urlencode($id);
    }
}
