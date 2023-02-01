<?php
/**
 * class to hold ManualPage data
 *
 * @package     UserManual
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold ManualPage data
 *
 * @package     UserManual
 * @subpackage  Model
 */
class UserManual_Model_ManualPage extends Tinebase_Record_Abstract
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
        'version'           => 3,
        'recordName'        => 'ManualPage',
        'recordsName'       => 'ManualPages', // ngettext('ManualPage', 'ManualPages', n)
        //'containerProperty' => 'container_id',
        'titleProperty'     => 'title',
//        'containerName'     => 'ManualPages list',
//        'containersName'    => 'ManualPages lists', // ngettext('ManualPages list', 'ManualPages lists', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => false,
        'hasAttachments'    => false,
        'exposeJsonApi'     => true,
        'exposeHttpApi'     => false,

        'createModule'      => false,
        'appName'           => 'UserManual',
        'modelName'         => 'ManualPage',

        'table'             => array(
            'name'    => 'usermanual_manualpage',
            'indexes' => array(
                'file' => array(
                    'columns' => array('file')
                ),
                'content'               => [
                    'columns'           => ['content'],
                    'flags'             => ['fulltext'],
                ],
            ),
        ),

        'fields'          => array(
            'title' => array(
                'type'        => 'text',
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Title', // _('Title')
                'queryFilter' => true
            ),
            'file' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Filename', // _('Filename')
            ),
            'content' => array(
                'type'        => self::TYPE_FULLTEXT, // html
                'nullable'    => true,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'       => 'Content', // _('Content')
                'queryFilter' => true
            ),
        )
    );
}
