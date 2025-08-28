<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Addressbook_Model_ExternalFreeBusyUrl extends Tinebase_Record_NewAbstract
{
    const FLD_URL = 'url';

    const MODEL_NAME_PART = 'ExternalFreeBusyUrl';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::RECORD_NAME               => 'External FreeBusy Url',  // ngettext('GENDER_External FreeBusy Url')
        self::RECORDS_NAME              => 'External FreeBusy Urls', // ngettext('External FreeBusy Url', 'External FreeBusy Urls', n)

        self::APP_NAME                  => Addressbook_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::FIELDS                    => [
            self::FLD_URL                   => [
                self::TYPE                      => self::TYPE_STRING,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}