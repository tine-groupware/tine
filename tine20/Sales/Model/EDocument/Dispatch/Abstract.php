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
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
        ],
    ];
}