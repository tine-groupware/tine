<?php

class CrewScheduling_Import_PollEventType_Csv extends \Tinebase_Import_Csv_Abstract
{
    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $_data = parent::_doConversions($_data);

        $_data['poll_id'] = $this->_getIdFromTitle($_data['poll_id'], CrewScheduling_Controller_Poll::class);
        $_data['event_type_id'] = $this->_getIdFromTitle($_data['event_type_id'], Calendar_Controller_EventType::class);

        return $_data;
    }
}