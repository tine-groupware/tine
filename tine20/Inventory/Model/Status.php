<?php
/**
 * tine Groupware
 *
 * @package     Inventory
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de> Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * Status Record Class
 * 
 * @package     Inventory
 * @subpackage  Model
 */
class Inventory_Model_Status extends Tinebase_Config_MCKeyFieldRecord
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Inventory';

    public const ORDERED = 'ORDERED';
    public const AVAILABLE = 'AVAILABLE';
    public const IN_USE = 'IN_USE';
    public const DEFECT = 'DEFECT';
    public const MISSING = 'MISSING';
    public const REMOVED = 'REMOVED';
    public const UNKNOWN = 'UNKNOWN';
    public const STORED = 'STORED';
    public const SOLD = 'SOLD';
    public const DESTROYED = 'DESTROYED';


    public const MODEL_NAME_PART = 'Status';
    public const FLD_IS_OPEN = 'is_open';


    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);


        $_definition[self::APP_NAME] = Inventory_Config::APP_NAME;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;

        $_definition[self::RECORD_NAME] = 'Status'; // gettext('GENDER_Status')
        $_definition[self::RECORDS_NAME] = 'Statuses'; // ngettext('Status', 'Statuses', n)

        $_definition[self::HAS_XPROPS] = false;

        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_VALUE, [
            self::FLD_IS_OPEN => [
                self::LABEL => 'Is open', // _('Is open')
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 5,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE => false,
            ],
        ]);

        unset($_definition[self::FIELDS][self::FLD_COLOR]);
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
