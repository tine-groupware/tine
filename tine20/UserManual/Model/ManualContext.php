<?php
/**
 * class to hold ManualContext data
 *
 * @package     UserManual
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold ManualContext data
 *
 * @package     UserManual
 * @subpackage  Model
 */
class UserManual_Model_ManualContext extends Tinebase_Record_Abstract
{
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
        'version'           => 2,
        'recordName'        => 'ManualContext',
        'recordsName'       => 'ManualContexts', // ngettext('ManualContext', 'ManualContexts', n)
        'titleProperty'     => 'title',
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'exposeJsonApi'     => false,
        'exposeHttpApi'     => false,

        'createModule'      => false,
        'appName'           => 'UserManual',
        'modelName'         => 'ManualContext',

        'table'             => array(
            'name'    => 'usermanual_manualcontext',
            'indexes' => array(
                'context' => array(
                    'columns' => array('context')
                )
            ),
        ),

        'fields'          => array(
            'context' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Context', // _('Context')
            ),
            'file' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Filename', // _('Filename')
            ),
            'title' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Title', // _('Title')
                'queryFilter' => true
            ),
            'target' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'       =>'Target', // _('Target')
            ),
            'chapter' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Chapter', // _('Chapter')
            ),
        )
    );
}
