<?php

declare(strict_types=1);

/**
 * @package     EventManager
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * class to hold Event Manager Checkbox option
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_CheckboxOption  extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'CheckboxOption';
    public const FLD_PRICE = 'price';
    public const FLD_TOTAL_PLACES = 'total_places';
    public const FLD_BOOKED_PLACES = 'booked_places';
    public const FLD_AVAILABLE_PLACES = 'available_places';
    public const FLD_DESCRIPTION = 'description';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE         => false,
        self::APP_NAME              => EventManager_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::RECORD_NAME           => 'Checkbox', // gettext('GENDER_Checkbox')
        self::RECORDS_NAME          => 'Checkboxes', // ngettext('Checkbox', 'Checkboxes', n)

        self::FIELDS => [
            self::FLD_PRICE     => [
                self::TYPE          => self::TYPE_MONEY,
                self::LABEL         => 'Price', // _('Price')
                self::NULLABLE      => true,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_TOTAL_PLACES  => [
                self::TYPE              => self::TYPE_INTEGER,
                self::LABEL             => 'Total places', // _('Total places')
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE  => true,
                self::DEFAULT_VAL       => 0,
            ],
            self::FLD_BOOKED_PLACES     => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::LABEL                 => 'Booked places', // _('Booked places')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE      => true,
                self::DEFAULT_VAL           => 0,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_AVAILABLE_PLACES  => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::LABEL                 => 'Available places', // _('Available places')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::ALLOW_CAMEL_CASE      => true,
                self::DEFAULT_VAL           => 0,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_DESCRIPTION   => [
                self::LABEL             => 'Description', //_('Description')
                self::TYPE              => self::TYPE_FULLTEXT,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

