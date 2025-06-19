<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * @package     Tinebase
 * @subpackage  Record
 *
 * @method static Tinebase_ModelConfiguration getConfiguration()
 */
trait Tinebase_Record_AbstractTrait
{
    /**
     * should data be validated on the fly(false) or only on demand(true)
     *
     * TODO it must not be public!
     *
     * @var bool
     */
    public $bypassFilters = false;

    /**
     * stores if values got modified after loaded via constructor
     *
     * @var bool
     */
    protected $_isDirty = false;

    protected static bool $_isHydratingFromBackend = false;

    public static function isHydratingFromBackend(): bool
    {
        return static::$_isHydratingFromBackend;
    }

    public static function doneHydratingFromBackend(): void
    {
        static::$_isHydratingFromBackend = false;
    }

    public function byPassFilters(): bool
    {
        return $this->bypassFilters;
    }

    /**
     * check if data got modified
     *
     * @return boolean
     */
    public function isDirty(): bool
    {
        return $this->_isDirty;
    }

    public function unsetDirty(): void
    {
        $this->_isDirty = false;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return false;
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSet
     * @param Tinebase_Record_RecordSetDiff $_recordSetDiff
     * @return bool
     */
    public static function applyRecordSetDiff(Tinebase_Record_RecordSet $_recordSet, Tinebase_Record_RecordSetDiff $_recordSetDiff)
    {
        return false;
    }

    public static function resolveRelationId(string $id, $record = null)
    {
        return $id;
    }

    public static function touchOnRelated(Tinebase_Model_Relation $relation): bool
    {
        return false;
    }

    public function applyFieldGrants(string $action, ?\Tinebase_Record_Interface $oldRecord = null)
    {
        $mc = static::getConfiguration();
        if (!$mc || empty($grantProtectedFields = $mc->grantProtectedFields)) {
            return;
        }
        if (!isset($grantProtectedFields[$action])) {
            if (!isset($grantProtectedFields[Tinebase_Controller_Record_Abstract::ACTION_ALL])) {
                return;
            }
            $grantProtectedFields = $grantProtectedFields[Tinebase_Controller_Record_Abstract::ACTION_ALL];
        } else {
            $grantProtectedFields = $grantProtectedFields[$action];
        }
        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = Tinebase_Core::getApplicationInstance(static::class, '', true);

        $access = [];
        $deny = [];
        foreach ($grantProtectedFields as $grant => $fields) {
            if ($ctrl->checkGrant($this, $grant, false)) {
                $access = array_unique(array_merge($access, $fields));
            } else {
                $deny = array_unique(array_merge($deny, $fields));
            }
        }
        if (empty($denyProperties = array_diff($deny, $access))) {
            return;
        }

        if (null === $oldRecord) {
            $bypassFilters = $this->bypassFilters;
            $this->bypassFilters = true;
            try {
                foreach ($denyProperties as $denyProperty) {
                    unset($this->{$denyProperty});
                }
            } finally {
                $this->bypassFilters = $bypassFilters;
            }
            if (true !== $this->bypassFilters) {
                $this->isValid(true);
            }
        } else {
            foreach ($denyProperties as $denyProperty) {
                $this->{$denyProperty} = $oldRecord->{$denyProperty};
            }
        }
    }

    public function prepareForCopy(): void
    {
        $mc = static::getConfiguration();
        $this->setId(null);
        foreach ($mc->getFields() as $prop => $def) {
            switch ($def[self::TYPE] ?? null) {
                case self::TYPE_RECORD:
                case self::TYPE_RECORDS:
                    if ($this->{$prop} && (($def[self::CONFIG][self::DEPENDENT_RECORDS] ?? false) || self::TYPE_JSON === ($def[self::CONFIG][self::STORAGE] ?? null))) {
                        $this->{$prop}->prepareForCopy();
                    }
                    break;
                case self::TYPE_DYNAMIC_RECORD:
                    if ($this->{$prop} && true === ($def[self::CONFIG][self::PERSISTENT] ?? false)) {
                        $this->{$prop}->prepareForCopy();
                    }
                    break;
                case self::TYPE_NUMBERABLE_INT:
                case self::TYPE_NUMBERABLE_STRING:
                    $this->{$prop} = null;
                    break;
            }
        }

        if ($mc->{self::HAS_RELATIONS}) {
            if (!$mc->{self::COPY_RELATIONS}) {
                $this->{self::FLD_RELATIONS} = null;
            } else {
                $this->{self::FLD_RELATIONS}->setId(null);
            }
        }

        // no need to prepare tags, they will just work

        if ($this->has(self::FLD_ALARMS)) {
            $this->{self::FLD_ALARMS}->setId(null);
            $this->{self::FLD_ALARMS}->sent_status = Tinebase_Model_Alarm::STATUS_PENDING;
        }

        // no need to prepare attachments, they will just work

        if ($this->has(self::FLD_NOTES)) {
            $this->{self::FLD_NOTES}->setId(null);
        }
    }

    public function notifyBroadcastHub(): bool
    {
        return true;
    }

    public function getPasswordFromProperty(string $field): ?string
    {
        $fieldConf = static::getConfiguration()->getFields()[$field];
        if (self::TYPE_PASSWORD !== $fieldConf[self::TYPE]) {
            throw new Tinebase_Exception($field . ' is not of type ' . self::TYPE_PASSWORD);
        }
        if (!isset($fieldConf[self::CONFIG][self::REF_ID_FIELD])) {
            throw new Tinebase_Exception_Record_DefinitionFailure($field . ' is missing ' . self::REF_ID_FIELD);
        }

        if (!($ccId = $this->{$fieldConf[self::CONFIG][self::REF_ID_FIELD]})) {
            return null;
        }
        try {
            /** @var Tinebase_Model_CredentialCache $cc */
            $cc = Tinebase_Auth_CredentialCache::getInstance()->get($ccId);
        } catch (Tinebase_Exception_NotFound) {
            return null;
        }
        $cc->key = Tinebase_Auth_CredentialCache_Adapter_Shared::getKey();
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($cc);
        return $cc->password;
    }
}