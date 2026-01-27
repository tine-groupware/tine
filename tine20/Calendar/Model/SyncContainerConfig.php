<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Calendar_Model_SyncContainerConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'SyncContainerConfig';

    public const FLD_CLOUD_ACCOUNT_ID = 'cloud_account_id';
    public const FLD_CALENDAR_PATH = 'calendar_path';
    public const FLD_CALENDAR_OWNER = 'calendar_owner';
    public const FLD_EXTERNAL_OWNER = 'external_owner';
    public const FLD_EXTERNAL_OWNER_LOCALLY_OVERWRITTEN = 'external_owner_locally_overwritten';
    public const FLD_EXTERNAL_CONTAINER_NAME = 'external_container_name';
    public const FLD_CONTAINER_NAME_LOCALLY_OVERWRITTEN = 'container_name_locally_overwritten';
    public const FLD_EXTERNAL_CONTAINER_COLOR = 'external_container_color';
    public const FLD_CONTAINER_COLOR_LOCALLY_OVERWRITTEN = 'container_color_locally_overwritten';
    public const FLD_OWN_PRIVILEGE_SET = 'own_privilege_set';
    public const FLD_LAST_SUCCESSFUL_SYNC = 'last_successful_sync';
    public const FLD_LAST_FAILED_SYNC = 'last_failed_sync';
    public const FLD_SYNC_HISTORY = 'sync_history';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Calendar_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,


        self::RECORD_NAME                  => 'External Calendar', // gettext('GENDER_External Calendar')
        self::RECORDS_NAME                 => 'External Calendars', // ngettext('External Calendar', 'External Calendars', n)

        self::JSON_EXPANDER                 => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_CLOUD_ACCOUNT_ID          => [],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_CLOUD_ACCOUNT_ID          => [
                self::LABEL                         => 'Cloud Account', // _('Cloud Account')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_CloudAccount::MODEL_NAME_PART,
                ],
                self::UI_CONFIG                     => [
                    'useEditPlugin'                     => true,
                ],
            ],
            self::FLD_CALENDAR_PATH             => [
                self::LABEL                         => 'Calendar Path', // _('Calendar Path')
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_EXTERNAL_CONTAINER_NAME   => [
                self::LABEL                         => 'Calendar Name', // _('Calendar Name')
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_CONTAINER_NAME_LOCALLY_OVERWRITTEN => [
                self::LABEL                         => 'Overwrite Calendar Name', // _('Overwrite Calendar Name')
                self::TYPE                          => self::TYPE_BOOLEAN,
            ],
            self::FLD_EXTERNAL_CONTAINER_COLOR => [
                self::LABEL                         => 'Color', // _('Color')
                self::TYPE                          => self::TYPE_HEX_COLOR,
            ],
            self::FLD_CONTAINER_COLOR_LOCALLY_OVERWRITTEN => [
                self::LABEL                         => 'Overwrite Color', // _('Overwrite Color')
                self::TYPE                          => self::TYPE_BOOLEAN,
            ],
            self::FLD_EXTERNAL_OWNER            => [
                self::LABEL                         => 'Owner Email', // _('Owner Email')
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_EXTERNAL_OWNER_LOCALLY_OVERWRITTEN => [
                self::LABEL                         => 'Overwrite Owner Email', // _('Overwrite Owner Email')
                self::TYPE                          => self::TYPE_BOOLEAN,
            ],
            self::FLD_OWN_PRIVILEGE_SET         => [
                self::TYPE                           => self::TYPE_JSON,
                self::UI_CONFIG                      => [
                    self::READ_ONLY                      => true,
                ],
            ],
            self::FLD_LAST_SUCCESSFUL_SYNC      => [
                self::TYPE                          => self::TYPE_DATETIME,
            ],
            self::FLD_LAST_FAILED_SYNC          => [
                self::TYPE                          => self::TYPE_DATETIME,
            ],
            self::FLD_SYNC_HISTORY              => [
                self::TYPE                          => self::TYPE_JSON,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function readValuesFromRemote(): void
    {
        Tinebase_Record_Expander::expandRecord($this);
        /** @var Tinebase_Model_CloudAccount $cloudAccount */
        $cloudAccount = $this->{self::FLD_CLOUD_ACCOUNT_ID};
        switch ($cloudAccount->{Tinebase_Model_CloudAccount::FLD_TYPE}) {
            case Tinebase_Model_CloudAccount_CalDAV::class:
                /** @var Tinebase_Model_CloudAccount_CalDAV $cloudConfig */
                $cloudConfig = $cloudAccount->{Tinebase_Model_CloudAccount::FLD_CONFIG};
                $calDavClient = $cloudConfig->getClient();
                $remoteValues = $calDavClient->getCollectionInfos($this->{self::FLD_CALENDAR_PATH}, [
                    Calendar_Backend_CalDav_Client::PROPERTY_CALENDAR_COLOR,
                    Calendar_Backend_CalDav_Client::PROPERTY_CURRENT_USER_PRIVILEGE_SET,
                    Calendar_Backend_CalDav_Client::PROPERTY_DISPLAY_NAME,
                    Calendar_Backend_CalDav_Client::PROPERTY_OWNER,
                ]);

                if (isset($remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_OWNER])) {
                    $this->{self::FLD_EXTERNAL_OWNER} = $remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_OWNER];
                } else {
                    $this->{self::FLD_EXTERNAL_OWNER} = null;
                }
                if (!$this->{self::FLD_EXTERNAL_OWNER_LOCALLY_OVERWRITTEN}) {
                    $this->{self::FLD_CALENDAR_OWNER} = $this->{self::FLD_EXTERNAL_OWNER};
                }
                if (isset($remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_CURRENT_USER_PRIVILEGE_SET])) {
                    $this->{self::FLD_OWN_PRIVILEGE_SET} = $remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_CURRENT_USER_PRIVILEGE_SET];
                } else {
                    $this->{self::FLD_OWN_PRIVILEGE_SET} = [];
                }
                if (isset($remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_DISPLAY_NAME])) {
                    $this->{self::FLD_EXTERNAL_CONTAINER_NAME} = $remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_DISPLAY_NAME];
                }
                if (isset($remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_CALENDAR_COLOR])) {
                    $this->{self::FLD_EXTERNAL_CONTAINER_COLOR} = $remoteValues[Calendar_Backend_CalDav_Client::PROPERTY_CALENDAR_COLOR];
                }

            default:
                throw new Tinebase_Exception_NotImplemented('not impltement for type ' . $cloudAccount->{Tinebase_Model_CloudAccount::FLD_TYPE});
        }
    }
}