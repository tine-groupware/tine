<?php declare(strict_types=1);
/**
 * class to hold Participant and Registrator Contact data
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Participant and Registrator Contact data
 *
 * @package     EventManager
 */
class EventManager_Model_Register_Contact extends Addressbook_Model_Contact
{
    public const MODEL_NAME_PART    = 'Register_Contact';
    public const TABLE_NAME         = 'eventmanager_register_contact';

    public const FLD_REGISTRATION_ID = 'registration_id';
    public const FLD_REGISTRATION_TYPE = 'registration_type';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 1;
        $_definition[self::APP_NAME] = EventManager_Config::APP_NAME;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::IS_DEPENDENT] = true;
        $_definition[self::INHERIT_PARENT_SYSTEM_CUSTOM_FIELDS] = true;
        $_definition[self::TABLE] = [
            self::NAME      => self::TABLE_NAME,
            self::INDEXES   => [
                self::FLD_ORIGINAL_ID => [
                    self::COLUMNS   => [self::FLD_ORIGINAL_ID],
                ],
            ],
        ];
        $_definition[self::EXPOSE_JSON_API] = false;
        $_definition[self::EXPOSE_HTTP_API] = false;

        $_definition[self::DENORMALIZATION_OF] = Addressbook_Model_Contact::class;
        $_definition[self::FIELDS][self::FLD_REGISTRATION_ID] = [
            self::TYPE                  => self::TYPE_RECORD,
            self::NORESOLVE             => true,
            self::VALIDATORS            => [
                Zend_Filter_Input::ALLOW_EMPTY  => false,
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
            ],
            self::CONFIG                => [
                self::APP_NAME              => EventManager_Config::APP_NAME,
                self::MODEL_NAME            => EventManager_Model_Registration::MODEL_NAME_PART,
                self::IS_PARENT             => true,
            ],
        ];
        $_definition[self::FIELDS][self::FLD_REGISTRATION_TYPE] = [
            self::TYPE                  => self::TYPE_STRING,
            self::VALIDATORS            => [
                Zend_Filter_Input::ALLOW_EMPTY  => false,
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                [Zend_Validate_InArray::class, [
                    EventManager_Model_Registration::FLD_PARTICIPANT,
                    EventManager_Model_Registration::FLD_REGISTRATOR,
                ]],
            ],
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
