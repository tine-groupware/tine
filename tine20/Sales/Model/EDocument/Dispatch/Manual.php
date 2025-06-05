<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Manual extends Sales_Model_EDocument_Dispatch_Abstract
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Manual';

    public const FLD_INSTRUCTIONS = 'instructions';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::RECORD_NAME] = 'Manual Dispatching'; // gettext('GENDER_Manual Dispatching')
        $_definition[self::RECORDS_NAME] = 'Manual Dispatchings'; // ngettext('Manual Dispatching', 'Manual Dispatchings', n)
        $_definition[self::TITLE_PROPERTY] = 'Dispatch manual: {{ record.instructions }}';

        $_definition[self::FIELDS][self::FLD_INSTRUCTIONS] = [
            self::TYPE              => self::TYPE_TEXT,
            self::LABEL             => 'Instructions', // _('Instructions')
        ];
    }

    protected static $_configurationObject = null;

    public function dispatch(Sales_Model_Document_Abstract $document, ?string $parentDispatchId = null): bool
    {
        $dispatchHistory = new Sales_Model_Document_DispatchHistory([
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => $document::class,
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => $document->getId(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_TRANSPORT => static::class,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_DATE => Tinebase_DateTime::now(),
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_REPORT => $this->{self::FLD_INSTRUCTIONS},
            Sales_Model_Document_DispatchHistory::FLD_TYPE => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_ID => Tinebase_Record_Abstract::generateUID(),
            Sales_Model_Document_DispatchHistory::FLD_PARENT_DISPATCH_ID => $parentDispatchId,
            Sales_Model_Document_DispatchHistory::FLD_DISPATCH_CONFIG => clone $this,
        ]);

        Sales_Controller_Document_DispatchHistory::getInstance()->create($dispatchHistory);

        return true;
    }
}