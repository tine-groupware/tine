<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */


class Tinebase_Model_CloudAccount_CalDAV extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'CloudAccount_CalDAV';

    public const FLD_URL = 'url';
    public const FLD_USERNAME = 'username';
    public const FLD_PWD = 'pwd';
    public const FLD_CC_ID = 'cc_id';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::RECORD_NAME               => 'CalDAV', // _('GENDER_CalDAV')
        self::RECORDS_NAME              => 'CalDAVs', // ngettext('CalDAV', 'CalDAVs', n)

        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::FIELDS                    => [
            self::FLD_URL                   => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'URL', // _('URL')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_USERNAME              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Username', // _('Username')
            ],
            self::FLD_PWD                   => [
                self::TYPE                      => self::TYPE_PASSWORD,
                self::LABEL                     => 'Password', // _('Password')
                self::CONFIG                    => [
                    self::CREDENTIAL_CACHE          => 'shared',
                    self::REF_ID_FIELD              => self::FLD_CC_ID,
                ],
            ],
            self::FLD_CC_ID                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::DISABLED                  => true, // TODO fixme Conny FE CONFIG? disabled? hide? etc.
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}