<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AuthGenericSmsMFAUserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_SmsUserConfig extends Tinebase_Auth_MFA_AbstractUserConfig
{
    public const MODEL_NAME_PART = 'MFA_SmsUserConfig';

    public const FLD_CELLPHONENUMBER = 'cellphonenumber';
    public const FLD_AUTH_TOKEN = 'authToken';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'SMS', // gettext('GENDER_SMS')
        self::RECORDS_NAME                   => 'SMSs', // ngettext('SMS', 'SMSs', n)
        self::TITLE_PROPERTY                => 'Security codes are send to: {{ cellphonenumber }}.', //_('Security codes are send to: {{ cellphonenumber }}.')

        self::FIELDS                        => [
            self::FLD_CELLPHONENUMBER           => [
                self::TYPE                          => self::TYPE_STRING,
                self::LABEL                         => 'Cell Phone Number', // _('Cell Phone Number')
                self::INPUT_FILTERS                 => [
                    Tinebase_Model_InputFilter_PhoneNumber::class
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_AUTH_TOKEN                => [
                self::TYPE                          => self::TYPE_STRING,
                self::SHY                           => true,
            ],
        ]
    ];

    public function toFEArray(?Tinebase_Model_FullUser $user = null): array
    {
        return [
            self::FLD_AUTH_TOKEN => $this->{self::FLD_AUTH_TOKEN},
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
