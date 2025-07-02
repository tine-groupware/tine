<?php

class Tinebase_Db_Profiler extends Zend_Db_Profiler
{
    public function setFilterElapsedSecs($minimumSeconds = null)
    {
        if (null === $minimumSeconds) {
            $this->_filterElapsedSecs = null;
        } else {
            $this->_filterElapsedSecs = (float) $minimumSeconds;
        }

        return $this;
    }

    public function queryEnd($queryId)
    {
        if (self::STORED === ($result = parent::queryEnd($queryId))) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' query time: '
                . $this->_queryProfiles[$queryId]->getElapsedSecs() . ' ' . $this->_queryProfiles[$queryId]->getQuery());
        }

        return $result;
    }
}
