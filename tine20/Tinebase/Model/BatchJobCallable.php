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

/**
 * BatchJobCallable Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_BatchJobCallable extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'BatchJobCallable';

    public const FLD_CLASS = 'class';
    public const FLD_METHOD = 'method';
    public const FLD_STATIC = 'static';
    public const FLD_PASS_OBJECT = 'passObject';
    public const FLD_APPEND_DATA = 'appendData';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => false,

        self::FIELDS                    => [
            self::FLD_CLASS                 => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_METHOD                => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_STATIC                => [
                self::TYPE                      => self::TYPE_BOOLEAN,
            ],
            self::FLD_PASS_OBJECT           => [
                self::TYPE                      => self::TYPE_BOOLEAN,
            ],
            self::FLD_APPEND_DATA           => [
                self::TYPE                      => self::TYPE_JSON,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function doCall(Tinebase_BatchJob_InOutData $inOutData): Tinebase_BatchJob_InOutData
    {
        if ($this->{self::FLD_PASS_OBJECT}) {
            $data = [$inOutData];
        } else {
            $data = $inOutData->getData();
        }

        if ($this->{self::FLD_APPEND_DATA}) {
            array_push($data, ...$this->{self::FLD_APPEND_DATA});
        }

        if ($this->{self::FLD_STATIC}) {
            $fn = fn() => call_user_func_array([$this->{self::FLD_CLASS}, $this->{self::FLD_METHOD}], $data);
        } else {
            $instance = call_user_func($this->{self::FLD_CLASS}, 'getInstance');
            $fn = fn() => call_user_func_array([$instance, $this->{self::FLD_METHOD}], $data);
        }

        if (!($result = $fn()) instanceof Tinebase_BatchJob_InOutData) {
            throw new Tinebase_Exception('bad out data' . $this->{self::FLD_CLASS} . '::' . $this->{self::FLD_METHOD} . '(): ' . print_r($result, true));
        }
        return $result;
    }

    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        if (!method_exists($this->{self::FLD_CLASS}, $this->{self::FLD_METHOD})) {
            throw new Tinebase_Exception($this->{self::FLD_CLASS} . '::' . $this->{self::FLD_METHOD} . '() does not exist');
        }
        $refMethod = new ReflectionMethod($this->{self::FLD_CLASS}, $this->{self::FLD_METHOD});
        if ($this->{self::FLD_STATIC}) {
            if (!$refMethod->isStatic()) {
                throw new Tinebase_Exception($this->{self::FLD_CLASS} . '::' . $this->{self::FLD_METHOD} . '() is not a static method');
            }
        } else {
            if ($refMethod->isStatic()) {
                throw new Tinebase_Exception($this->{self::FLD_CLASS} . '::' . $this->{self::FLD_METHOD} . '() is a static method');
            }
            if (!method_exists($this->{self::FLD_CLASS}, 'getInstance')) {
                throw new Tinebase_Exception($this->{self::FLD_CLASS} . '::getInstance() does not exist');
            }
        }
    }
}
