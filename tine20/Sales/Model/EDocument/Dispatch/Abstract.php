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
                    self::DEFAULT_FROM_CONFIG       => [
                        self::APP_NAME                  => Sales_Config::APP_NAME,
                        self::CONFIG                    => Sales_Config::DEFAULT_EDOCUMENT_DISPATCH_DOCUMENT_TYPES,
                    ],
                ],
                self::VALIDATORS                => [
                    [Tinebase_Record_Validator_SubValidate::class],
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

    public function getRequiredDocumentTypes(): array
    {
        $requiredTypes = [];
        foreach ($this->{self::FLD_DOCUMENT_TYPES} as $documentType) {
            $requiredTypes[] = $documentType->{Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE};
        }
        return array_unique($requiredTypes);
    }

    public function getMissingDocumentTypes(Sales_Model_Document_Abstract $document): array
    {
        $missingDoyTypes = [];
        $attachedDocs = $document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}->filter(Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ, $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ});
        foreach ($this->getRequiredDocumentTypes() as $docType) {
            if (null === $attachedDocs->find(Sales_Model_Document_AttachedDocument::FLD_TYPE, $docType)) {
                $missingDoyTypes[] = $docType;
            }
        }
        return array_unique($missingDoyTypes);
    }
}