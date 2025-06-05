<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Calendar_Model_EventType extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'EventType';
    public const TABLE_NAME = 'cal_event_type';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'           => 1,
        'recordName'        => 'Event Type',  // gettext('GENDER_Event Type')
        'recordsName'       => 'Event Types', // ngettext('Event Type', 'Event Types', n)
        'containerProperty' => NULL,
        'titleProperty'     => 'name',
        'hasRelations'      => false,
        'hasCustomFields'   => true,
        'hasSystemCustomFields' => true,
        'hasNotes'          => false,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => false,

        'createModule'      => false,

        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,

        'appName'           => 'Calendar', // _('Calendar')
        'modelName'         => 'EventType',

        'table'             => array(
            'name'                  => self::TABLE_NAME,
            'uniqueConstraints'     => array(
                'name'                  => array(
                    'columns'               => array('name', 'deleted_time')
                )
            )
        ),

        'fields'          => array(
            'short_name' => array(
                'type'       => 'string',
                'length'     => 3,
                'nullable'   => true,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'       => 'Short Name', // _('Short Name')
                'queryFilter' => TRUE
            ),
            'name' => array(
                'type'       => 'string',
                'length'     => 255,
                'nullable'   => false,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Name', // _('Name')
                'queryFilter' => TRUE
            ),
            'description' => array(
                'type'       => 'fulltext',
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Description', // _('Description')
            ),
            'color' => array(
                'type'       => 'hexcolor',
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Color', // _('Color')
            )
        )
    );

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return true;
    }
}
