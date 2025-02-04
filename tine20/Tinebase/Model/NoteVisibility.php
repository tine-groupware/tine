<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 *
 */

/**
 * defines the datatype for note visibility
 * 
 * @package     Tinebase
 * @subpackage  Notes
 */
class Tinebase_Model_NoteVisibility extends Tinebase_Config_KeyFieldRecord
{
    public const MODEL_NAME_PART = 'Visibility';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = Tinebase_Config::APP_NAME;

    protected $_additionalValidators = array(
        'icon_class'             => array('allowEmpty' => true         ),
        'is_user_type'                => array('allowEmpty' => true         ),
    );
}
