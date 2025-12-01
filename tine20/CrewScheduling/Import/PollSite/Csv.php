<?php

class CrewScheduling_Import_PollSite_Csv extends \Tinebase_Import_Csv_Abstract
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
        $_data['site_id'] = $this->_getIdFromTitle($_data['site_id'], Addressbook_Controller_Contact::class);

        return $_data;
    }
}
