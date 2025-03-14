<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

abstract class Sales_Model_EDocument_Dispatch_Abstract extends Tinebase_Record_NewAbstract implements Sales_Model_EDocument_Dispatch_Interface
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Abstract';
    public const FLD_DOCUMENT_TYPES = 'document_types';
    public const FLD_EXPECTS_FEEDBACK = 'expects_feedback';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::RECORD_NAME               => 'Dispatch Config', // ngettext('Dispatch Config', 'Dispatch Configs', n)
        self::RECORDS_NAME              => 'Dispatch Config', // gettext('GENDER_Dispatch Config')
//        self::TITLE_PROPERTY            => '{{ "Dispatch Config" }}',

        self::FIELDS                    => [
            self::FLD_DOCUMENT_TYPES        => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Document Types', // _('Document Types')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_EDocument_Dispatch_DocumentType::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::DEFAULT_VALUE => [[Tinebase_Core::class, 'createInstance'], Tinebase_Record_RecordSet::class, Sales_Model_EDocument_Dispatch_DocumentType::class, [
                        [Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_PAPERSLIP],
                        [Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_EDOCUMENT],
                    ]],
                ],
            ],
            self::FLD_EXPECTS_FEEDBACK      => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::LABEL                     => 'Expects Feedback', // _('Expects Feedback')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => false,
                ],
            ],
        ],
    ];
}