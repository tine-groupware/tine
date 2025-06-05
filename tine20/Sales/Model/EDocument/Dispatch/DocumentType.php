<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch document type data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_DocumentType extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_DocumentType';
    public const FLD_DOCUMENT_TYPE = 'document_type';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Document Type', // ngettext('Document Type', 'Document Types', n)
        self::RECORDS_NAME              => 'Document Types', // gettext('GENDER_Document Type')
        self::TITLE_PROPERTY            => '{{ keyField("' . Sales_Config::APP_NAME . '", "' . Sales_Config::ATTACHED_DOCUMENT_TYPES . '", ' . self::FLD_DOCUMENT_TYPE . ') }}',
        self::IS_METADATA_MODEL_FOR     => self::FLD_DOCUMENT_TYPE,

        self::FIELDS                    => [
            self::ID                        => [
                self::TYPE                      => self::TYPE_STRING,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                    Zend_Filter_Input::DEFAULT_VALUE => [[Tinebase_Record_Abstract::class, 'generateUID']],
                ],
            ],
            self::FLD_DOCUMENT_TYPE         => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::LABEL                     => 'Document Type', // _('Document Type')
                self::NAME                      => Sales_Config::ATTACHED_DOCUMENT_TYPES,
                self::CONFIG                    => [
                    self::VALIDATE                  => true,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
        ],
    ];

    protected static $_configurationObject = null;
}