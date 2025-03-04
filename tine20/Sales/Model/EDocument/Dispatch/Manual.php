<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Manual extends Tinebase_Record_NewAbstract implements Sales_Model_EDocument_Dispatch_Interface
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Manual';
    public const FLD_INSTRUCTIONS = 'instructions';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Manual Dispatching', // gettext('GENDER_Manual Dispatching')
        self::RECORDS_NAME              => 'Manual Dispatchings', // ngettext('Manual Dispatching', 'Manual Dispatchings', n)
        self::TITLE_PROPERTY            => self::FLD_INSTRUCTIONS,

        self::FIELDS                    => [
            self::FLD_INSTRUCTIONS           => [
                self::LABEL                     => 'Instructions', // _('Instructions')
                self::TYPE                      => self::TYPE_FULLTEXT,
            ],
        ],
    ];
    protected static $_configurationObject = null;

    public function dispatch(Sales_Model_Document_Abstract $document): void
    { // no op
    }
}