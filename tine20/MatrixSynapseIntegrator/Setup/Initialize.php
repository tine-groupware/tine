<?php
/**
 * Tine 2.0
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 *  MatrixSynapseIntegrator setup initialize class
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 */
class  MatrixSynapseIntegrator_Setup_Initialize extends Setup_Initialize
{
    public static $customfields = [
        [
            'app' => Addressbook_Config::APP_NAME,
            'model' => Addressbook_Model_Contact::class,
            'cfields' => [
                [
                    'is_system' => true,
                    'name' => MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_MATRIX_ID,
                    TMCC::LABEL => 'Matrix-ID', // _('Matrix-ID')
                    TMCC::UI_CONFIG => [
                        'order' => '29',
                        'group' => 'Contact Information',
                    ],
                    TMCC::TYPE => TMCC::TYPE_TEXT,
                    TMCC::SPECIAL_TYPE => Addressbook_Model_ContactProperties_InstantMessenger::class,
                    TMCC::INPUT_FILTERS => [
                        Zend_Filter_StringTrim::class                    ],
                ]
            ]
        ],
    ];

    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        MatrixSynapseIntegrator_Scheduler_Task::addExportDirectoryTask($scheduler);
    }

    public static function initializeCustomFields()
    {
        self::createCustomFields(static::$customfields);
    }
}
