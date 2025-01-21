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
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Email';

    public const FLD_URL = 'url';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);


        $_definition[self::FIELDS][self::FLD_URL] = [
            self::TYPE              => self::TYPE_STRING,
            self::LABEL             => 'URL', // _('URL')
        ];
    }

    public function dispatch(Sales_Model_Document_Abstract $document): void
    {
        // TODO: Implement dispatch() method.
    }

    protected static $_configurationObject = null;
}
{}