<?php declare(strict_types=1);
/**
 * tine
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Sales_Model_EDocument_VATProcedure extends Tinebase_Config_KeyFieldRecord
{
    public const MODEL_NAME_PART = 'EDocument_VATProcedure';
    public const FLD_UNTDID_5305 = 'untdid_5305';
    public const FLD_VATEX = 'vatex';

    protected $_additionalValidators = [
        self::FLD_UNTDID_5305   => ['allowEmpty' => true ],
        self::FLD_VATEX         => ['allowEmpty' => true ],
    ];

    public function runConvertToRecord()
    {
        parent::runConvertToRecord();
        if (is_string($this->{self::FLD_VATEX}) && is_array($decoded = json_decode($this->{self::FLD_VATEX}, true))) {
            $this->{self::FLD_VATEX} = $decoded;
        }
    }

    public function runConvertToData()
    {
        parent::runConvertToData();
        if (is_array($this->{self::FLD_VATEX})) {
            $this->{self::FLD_VATEX} = json_encode($this->{self::FLD_VATEX});
        }
    }

    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        if (is_string($this->{self::FLD_VATEX}) && is_array($decoded = json_decode($this->{self::FLD_VATEX}, true))) {
            $this->{self::FLD_VATEX} = $decoded;
        }
    }

    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);
        if (is_array($result[self::FLD_VATEX] ?? null)) {
            $result[self::FLD_VATEX] = json_encode($result[self::FLD_VATEX]);
        }
        return $result;
    }
}
