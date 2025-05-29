<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Model_FileLocation extends Tinebase_Record_NewAbstract implements Tinebase_Model_FileLocation_Interface
{
    use Tinebase_Model_FileLocation_NoChgAfterInitTrait;
    use Tinebase_Model_FileLocation_DelegatorTrait;

    public const MODEL_NAME_PART = 'FileLocation';

    public const FLD_LOCATION = 'location';


    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'File Location', // ngettext('File Location', 'File Locations', n)
        self::RECORDS_NAME                  => 'File Locations', // gettext('GENDER_File Location')

        self::FIELDS                        => [
            self::FLD_LOCATION              => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_JsonRecordWrapper::MODEL_NAME_PART,
                    self::PERSISTENT                    => true,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected function _init(): void
    {
        if ($this->_init) {
            return;
        }
        $this->_init = true;
        $this->delegator = $this->{self::FLD_LOCATION}->{Tinebase_Model_JsonRecordWrapper::FLD_RECORD};
    }
}