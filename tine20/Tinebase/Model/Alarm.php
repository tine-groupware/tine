<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_Model_Alarm
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string     id
 * @property string     record_id
 * @property string     model
 * @property string     alarm_time
 * @property string     minutes_before
 * @property string     sent_time
 * @property string     sent_status
 * @property string     sent_message
 * @property string     options
 */
class Tinebase_Model_Alarm extends Tinebase_Record_NewAbstract implements Tinebase_Record_PerspectiveInterface
{
    use Tinebase_Record_PerspectiveTrait;

    public const TABLE_NAME = 'alarm';
    public const MODEL_NAME_PART = 'Alarm';

    /**
     * pending status
     *
     */
    public const STATUS_PENDING = 'pending';
    
    /**
     * failure status
     *
     */
    public const STATUS_FAILURE = 'failure';

    /**
     * success status
     *
     */
    public const STATUS_SUCCESS = 'success';
    
    /**
     * minutes_before value for custom alarm time
     */
    public const OPTION_CUSTOM = 'custom';
    
    /**
     * ack client option
     */
    public const OPTION_ACK_CLIENT = 'ack_client';
    
    /**
     * ack ip option
     */
    public const OPTION_ACK_IP = 'ack_ip';

    public const OPT_SKIP = 'skip';
    public const OPT_ACK = 'ack';
    public const OPT_SNOOZE = 'snooze';
    
    /**
     * default minutes_before value
     */
    public const DEFAULT_MINUTES_BEFORE = 15;

    public const FLD_ALARM_TIME = 'alarm_time';
    public const FLD_MINUTES_BEFORE = 'minutes_before';
    public const FLD_MODEL = 'model';
    public const FLD_OPTIONS = 'options';
    public const FLD_RECORD_ID = 'record_id';
    public const FLD_SENT_MESSAGE = 'sent_message';
    public const FLD_SENT_STATUS = 'sent_status';
    public const FLD_SENT_TIME = 'sent_time';
    public const FLD_SKIP = 'skip';
    public const FLD_ACK_TIME = 'ack_time';
    public const FLD_SNOOZE_TIME = 'snooze_time';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION           => 4,
        self::IS_DEPENDENT      => true,
        self::RECORD_NAME       => 'Alarm',
        self::RECORDS_NAME      => 'Alarms', // ngettext('Alarm', 'Alarms', n)

        self::CREATE_MODULE     => false,
        self::EXPOSE_HTTP_API   => false,
        self::EXPOSE_JSON_API   => false,

        self::APP_NAME          => Tinebase_Config::APP_NAME,
        self::MODEL_NAME        => self::MODEL_NAME_PART,

        self::TABLE             => [
            self::NAME              => self::TABLE_NAME,
            self::INDEXES           => [
                'record_id__model'      => [
                    self::COLUMNS           => [self::FLD_RECORD_ID, self::FLD_MODEL],
                ],
            ],
        ],

        self::FIELDS            => [
            self::FLD_RECORD_ID     => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 40,
            ],
            self::FLD_MODEL         => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 40,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
            ],
            self::FLD_ALARM_TIME    => [
                self::TYPE              => self::TYPE_DATETIME,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
            ],
            self::FLD_MINUTES_BEFORE=> [
                self::TYPE              => self::TYPE_INTEGER,
                self::DOCTRINE_IGNORE   => true,
            ],
            self::FLD_SENT_TIME     => [
                self::TYPE              => self::TYPE_DATETIME,
                self::NULLABLE          => true,
            ],
            self::FLD_SENT_STATUS   => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 32,
                self::DEFAULT_VAL       => self::STATUS_PENDING,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'presence' => 'required',
                    ['InArray', [
                        self::STATUS_PENDING,
                        self::STATUS_FAILURE,
                        self::STATUS_SUCCESS,
                    ]],
                    Zend_Filter_Input::DEFAULT_VALUE => self::STATUS_PENDING,
                ],
            ],
            self::FLD_SENT_MESSAGE  => [
                self::TYPE              => self::TYPE_TEXT,
                self::NULLABLE          => true,
                self::DEFAULT_VAL       => null,
            ],
            self::FLD_OPTIONS       => [
                self::TYPE              => self::TYPE_TEXT,
                self::NULLABLE          => true,
                self::DEFAULT_VAL       => null,
            ],
            self::FLD_SKIP          => [
                self::TYPE              => self::TYPE_BOOLEAN,
                self::IS_PERSPECTIVE    => true,
            ],
            self::FLD_ACK_TIME      => [
                self::TYPE              => self::TYPE_DATETIME,
                self::IS_PERSPECTIVE    => true,
            ],
            self::FLD_SNOOZE_TIME   => [
                self::TYPE              => self::TYPE_DATETIME,
                self::IS_PERSPECTIVE    => true,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
    
    /**
     * set alarm time depending on another date with minutes_before
     *
     * @param Tinebase_DateTime $_date
     */
    public function setTime(Tinebase_DateTime $_date)
    {
        if (! isset($this->minutes_before)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' minutes_before not set, reverting to default value(' . self::DEFAULT_MINUTES_BEFORE . ')');
            $this->minutes_before = self::DEFAULT_MINUTES_BEFORE;
        }
        
        if ($this->minutes_before !== self::OPTION_CUSTOM) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Calculating alarm_time ...');
            $date = clone $_date;
            $this->alarm_time = $date->subMinute(round($this->minutes_before));
        }
        
        $this->setOption(self::OPTION_CUSTOM, $this->minutes_before === self::OPTION_CUSTOM);
    }

    /**
     * set minutes_before depending on another date with alarm_time
     *
     * @param Tinebase_DateTime $_date
     */
    public function setMinutesBefore(Tinebase_DateTime $_date)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Current alarm: ' . print_r($this->toArray(), TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Date: ' . $_date);
        
        if ($this->getOption(self::OPTION_CUSTOM) !== TRUE) {
            $dtStartTS = $_date->getTimestamp();
            $alarmTimeTS = $this->alarm_time->getTimestamp();
            $this->minutes_before = $dtStartTS < $alarmTimeTS ? 0 : round(($dtStartTS - $alarmTimeTS) / 60);
            
        } else {
            $this->minutes_before = self::OPTION_CUSTOM;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' Resulting minutes_before: ' . $this->minutes_before);
    }
    
    /**
     * sets an option
     *
     * @param string|array $_key
     */
    public function setOption($_key, mixed $_value = null)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        
        $_key = is_array($_key) ?: array($_key => $_value);
        foreach ($_key as $key => $value) {
            $options[$key] = $value;
        }
        
        $this->options = Zend_Json::encode($options);
    }
    
    /**
     * gets an option
     * 
     * @param  string $_key
     * @return mixed
     */
    public function getOption($_key)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        return $options[$_key] ?? null;
    }
}
