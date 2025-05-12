<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Upload extends Sales_Model_EDocument_Dispatch_Abstract
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Upload';

    public const FLD_URL = 'url';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::RECORD_NAME] = 'Upload'; // gettext('GENDER_Upload')
        $_definition[self::RECORDS_NAME] = 'Uploads'; // ngettext('Upload', 'Uploads', n)
        $_definition[self::TITLE_PROPERTY] = 'Upload to: {{ record.url }}';

        $_definition[self::FIELDS][self::FLD_URL] = [
            self::TYPE              => self::TYPE_STRING,
            self::SPECIAL_TYPE      => self::SPECIAL_TYPE_URL,
            self::LABEL             => 'URL', // _('URL')
            self::VALIDATORS        => [
                Zend_Filter_Input::ALLOW_EMPTY => false,
                Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
            ],
        ];
    }

    public function dispatch(Sales_Model_Document_Abstract $document, ?string $parentDispatchId = null): bool
    {
        $t = Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME);
        $dispatchHistory = new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $t->_('upload to: ') . $this->{self::FLD_URL},
            Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => Tinebase_Record_Abstract::generateUID(),
            Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_CONFIG => clone $this,
        ]);

        Sales_Controller_Document_DispatchHistory::getInstance()->create($dispatchHistory);

        return true;
    }

    protected static $_configurationObject = null;
}