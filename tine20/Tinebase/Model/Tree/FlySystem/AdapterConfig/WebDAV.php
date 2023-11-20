<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Model_Tree_FlySystem_AdapterConfig_WebDAV extends Tinebase_Record_NewAbstract implements Tinebase_Model_Tree_FlySystem_AdapterConfig_Interface
{
    public const MODEL_NAME_PART = 'Tree_FlySystem_AdapterConfig_WebDAV';

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
        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::FIELDS        => [
            self::FLD_URL => [
                self::TYPE          => self::TYPE_STRING,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_USERNAME => [
                self::TYPE          => self::TYPE_STRING,
                self::VALIDATORS    => [
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_CC_ID => [
                self::TYPE          => self::TYPE_STRING,
            ],
            self::FLD_PWD => [
                self::TYPE          => self::TYPE_PASSWORD,
                self::CONFIG        => [
                    self::CREDENTIAL_CACHE => 'shared',
                    self::REF_ID_FIELD => self::FLD_CC_ID,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function getFlySystemAdapter(): \League\Flysystem\FilesystemAdapter
    {
        return new \League\Flysystem\WebDAV\WebDAVAdapter(
            new \Sabre\DAV\Client([
                'baseUri' => $this->{self::FLD_URL},
                'userName' => $this->{self::FLD_USERNAME},
                'password' => $this->getPasswordFromProperty(self::FLD_PWD),
                'authType' => \CURLAUTH_BASIC,
            ]),
        );
    }
}