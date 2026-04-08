<?php

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

class Poll_Model_Poll extends Tinebase_Record_NewAbstract
{
    /**
     * define DB fields
     */
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const START = 'start';
    const END = 'end';
    const QUESTIONS = 'questions';

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
    protected static $_modelConfiguration = array(
        self::VERSION => 1,
        self::RECORD_NAME => 'Poll', // _('Poll') ngettext('Poll', 'Polls', n)
        self::RECORDS_NAME => 'Polls', // _('Polls')
        self::TITLE_PROPERTY => self::NAME,
        self::HAS_RELATIONS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::HAS_NOTES => true,
        self::HAS_TAGS => true,
        self::MODLOG_ACTIVE => true,
        'hasAttachments' => true,

        self::CREATE_MODULE => true,

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,

        self::APP_NAME => 'Poll',
        self::MODEL_NAME => 'Poll',

        self::TABLE => [
            self::NAME => 'Poll_Poll',
        ],

        self::FIELDS => [
            self::NAME=> [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => false,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL => 'Name', // _('Name')
                self::QUERY_FILTER => TRUE
            ],
            self::DESCRIPTION => [
                self::TYPE => self::TYPE_FULLTEXT,
                self::NULLABLE  => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL  => 'Description', // _('Description')
            ],
            self::START => [
                self::TYPE => self::TYPE_DATETIME,
                self::NULLABLE  => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL  => 'Start',
            ],
            self::END => [
                self::TYPE => self::TYPE_DATETIME,
                self::NULLABLE  => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'End', // _('End')
            ],
            /*'status' => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Status',
                self::NAME => 'status',
            ),
            */
            self::QUESTIONS => [
                self::TYPE => 'json',
                self::LABEL => 'question', // _('Question')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true]
            ]
        ]
    );
}
