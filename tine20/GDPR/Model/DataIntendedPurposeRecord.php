<?php
/**
 * class to hold DataIntendedPurposeRecord data
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold DataIntendedPurposeRecord data
 *
 * @package     GDPR
 * @subpackage  Model
 * 
 * @property    string                          $id
 * @property    GDPR_Model_DataIntendedPurpose  $intendedPurpose
 * @property    Tinebase_DateTime               $agreeDate
 * @property    string                          $agreeComment
 * @property    Tinebase_DateTime               $withdrawDate
 * @property    string                          $withdrawComment
 */
class GDPR_Model_DataIntendedPurposeRecord extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'DataIntendedPurposeRecord';
    public const FLD_INTENDEDPURPOSE = 'intendedPurpose';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'version' => 2,
        'recordName' => 'Purpose of processing',   // _('GENDER_Purpose of processing')
        'recordsName' => 'Purposes of processing', // ngettext('Purpose of processing', 'Purposes of processing', n)
        'titleProperty' => 'id',
        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'exposeJsonApi' => false,
        'exposeHttpApi' => false,

        'singularContainerMode' => false,
        'hasPersonalContainer' => false,

        'copyEditAction' => false,
        'multipleEdit' => false,
        
        'createModule' => false,
        'appName' => GDPR_Config::APP_NAME,
        'modelName' => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME      => 'gdpr_dataintendedpurposerecords',
            self::INDEXES   => [
                'intendedPurpose'       => [
                    self::COLUMNS           => ['intendedPurpose', 'record'],
                ],
                'record'                => [
                    self::COLUMNS           => ['record', 'intendedPurpose'],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'intendedPurpose_fk' => [
                    'targetEntity' => GDPR_Model_DataIntendedPurpose::class,
                    'fieldName' => 'intendedPurpose',
                    'joinColumns' => [[
                        'name' => 'intendedPurpose',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::LANGUAGES_AVAILABLE => [
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => GDPR_Config::LANGUAGES_AVAILABLE,
            self::CONFIG => [
                self::APP_NAME => GDPR_Config::APP_NAME,
            ],
        ],
        
        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_INTENDEDPURPOSE => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        GDPR_Model_DataIntendedPurpose::FLD_NAME            => [],
                        GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION     => [],
                    ]
                ]
            ],
        ],

        self::FIELDS => [
            self::FLD_INTENDEDPURPOSE       => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => GDPR_Config::APP_NAME,
                    self::MODEL_NAME        => GDPR_Model_DataIntendedPurpose::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Purpose of processing', // _('Purpose of processing')
                self::QUERY_FILTER      => true,
                self::ALLOW_CAMEL_CASE  => true,
            ],
            'record'                => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 40,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::DISABLED          => true,
            ],
            'agreeDate' => [
                self::TYPE              => self::TYPE_DATETIME,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Agreement date', // _('Agreement date'),
                self::FILTER_DEFINITION => [
                    self::FILTER            => Tinebase_Model_Filter_DateTime::class,
                    self::OPTIONS           => [
                        Tinebase_Model_Filter_Date::BEFORE_OR_IS_NULL => true,
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ],
                self::ALLOW_CAMEL_CASE  => true,
            ],
            'agreeComment' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Agreement comment', // _('Agreement comment')
                self::ALLOW_CAMEL_CASE  => true,
            ],
            'withdrawDate' => [
                self::TYPE              => self::TYPE_DATETIME,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Withdrawal date', // _('Withdrawal date')
                self::FILTER_DEFINITION => [
                    self::FILTER            => Tinebase_Model_Filter_DateTime::class,
                    self::OPTIONS           => [
                        Tinebase_Model_Filter_Date::BEFORE_OR_IS_NULL => true,
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ],
                self::ALLOW_CAMEL_CASE  => true,
            ],
            'withdrawComment' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Withdrawal comment', // _('Withdrawal comment')
                self::ALLOW_CAMEL_CASE  => true,
            ],
        ]
    ];
}
