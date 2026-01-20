<?php declare(strict_types=1);
/**
 * denormalized adb address
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class EventManager_Model_Register_ContactPropertiesAddress extends Addressbook_Model_ContactProperties_Address
{
    public const MODEL_NAME_PART    = 'Register_ContactPropertiesAddress';
    public const TABLE_NAME         = 'eventmanager_register_contact_address';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 1;
        $_definition[self::APP_NAME] = EventManager_Config::APP_NAME;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME      => self::TABLE_NAME,
            self::INDEXES   => [
                self::FLD_ORIGINAL_ID => [
                    self::COLUMNS   => [self::FLD_ORIGINAL_ID],
                ],
            ],
        ];

        $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_CONTACT_ID]
            [self::TARGET_ENTITY] = EventManager_Model_Register_Contact::class;

        $_definition[self::DENORMALIZATION_OF] = Addressbook_Model_ContactProperties_Address::class;

        $_definition[self::FIELDS][self::FLD_CONTACT_ID][self::CONFIG][self::APP_NAME] = EventManager_Config::APP_NAME;
        $_definition[self::FIELDS][self::FLD_CONTACT_ID][self::CONFIG][self::MODEL_NAME] = EventManager_Model_Register_Contact::MODEL_NAME_PART;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
