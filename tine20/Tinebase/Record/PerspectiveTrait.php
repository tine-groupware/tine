<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * @property bool $_isDirty
 * @method static Tinebase_ModelConfiguration getConfiguration()
 */
trait Tinebase_Record_PerspectiveTrait
{
    protected array $_perspectiveData = [];
    protected ?Tinebase_Record_Interface $_perspectiveRec = null;

    public function setPerspectiveData(string $property, array $data): mixed
    {
        $this->_perspectiveData[$property] = $data;
        $pKey = $this->getPerspectiveKey($this->getPerspectiveRecord());
        return $data && array_key_exists($pKey, $data) ? $data[$pKey] :
            static::getConfiguration()->getFields()[$property][TMCC::PERSPECTIVE_DEFAULT];
    }

    public function getPerspectiveData(string $property): ?array
    {
        return $this->_perspectiveData[$property] ?? null;
    }

    public function setPerspectiveTo(Tinebase_Record_Interface $perspectiveRec): void
    {
        $pKey = $this->getPerspectiveKey($perspectiveRec);
        $oldPKey = $this->getPerspectiveKey($this->getPerspectiveRecord());
        $isDirty = $this->_isDirty;
        $fields = static::getConfiguration()->getFields();

        foreach ($this->_perspectiveData as $property => &$data) {
            if ($fields[$property][TMCC::PERSPECTIVE_DEFAULT] === $this->{$property}) {
                unset($data[$oldPKey]);
            } else {
                $data[$oldPKey] = $this->{$property};
            }
            $this->{$property} = $data[$pKey] ?? $fields[$property][TMCC::PERSPECTIVE_DEFAULT];
        }
        $this->_perspectiveRec = $perspectiveRec;
        $this->_isDirty = $isDirty;
    }

    public function getPerspectiveRecord(): Tinebase_Record_Interface
    {
        return $this->_perspectiveRec ?? Tinebase_Core::getUser();
    }

    public function getPerspectiveKey(Tinebase_Record_Interface $record): string
    {
        return $record::class . $record->getId();
    }
}