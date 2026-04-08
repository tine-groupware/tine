<?php

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

class Poll_Model_Answer extends Tinebase_Record_Abstract
{

    /**
     * define DB fields
     */
    const POLL_ID = 'poll_id';
    const USER_ID = 'user_id';
    const ANSWER = 'answer';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Poll';

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
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME => 'Answer', // _('Answer') ngettext('Answer', 'Answers', n)
        self::RECORDS_NAME => 'Answers', // _('Answers')
        self::HAS_RELATIONS => true,

        self::MODLOG_ACTIVE => true,

        'hasAttachments' => true,


        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,

        self::APP_NAME => 'Poll',
        self::MODEL_NAME => 'Answer',

        self::TABLE => [
            self::NAME => 'Poll_Answer',
        ],

        self::FIELDS => [
            self::POLL_ID => [
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                self::TYPE => 'record',
                'sortable' => FALSE,
                self::CONFIG => [
                    self::APP_NAME => 'Poll',
                    self::MODEL_NAME => 'Poll',
                    self::TITLE_PROPERTY => 'id',
                    'isParent' => TRUE
                ]
            ],
            self::USER_ID => [
                self::TYPE => 'record',
                self::LABEL => 'Name', // _('Name')
                self::NULLABLE => false,
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'recursiveResolving' => true,
                self::CONFIG => [
                    self::REF_ID_FIELD => 'id',
                    self::LENGTH => 40,
                    self::APP_NAME => 'Addressbook',
                    self::MODEL_NAME => 'Contact'
                ]
            ],
            self::ANSWER => [
                self::NULLABLE => true,
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::TYPE => 'json',
                self::LABEL => 'answer', // _('Answer')
            ],
        ]
    ];
}
