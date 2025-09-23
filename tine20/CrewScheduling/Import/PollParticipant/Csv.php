<?php

class CrewScheduling_Import_PollParticipant_Csv extends \Tinebase_Import_Csv_Abstract
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

        $_data = $this->_convertPollId($_data);
        $_data = $this->_convertContactId($_data);

        return $_data;
    }

    private function _convertPollId(array $_data): array
    {
        if (!isset($_data['poll_id']) || !is_string($_data['poll_id']) || $_data['poll_id'] === '') {
            return $_data;
        }

        $pollRecord = \CrewScheduling_Controller_Poll::getInstance()->getRecordByTitleProperty(trim($_data['poll_id']));
        if ($pollRecord) {
            $_data['poll_id'] = $pollRecord->id;
        }

        return $_data;
    }

    private function _convertContactId(array $_data): array
    {
        if (!isset($_data['contact_id']) || !is_string($_data['contact_id']) || $_data['contact_id'] === '') {
            return $_data;
        }

        $contactRecord = \Addressbook_Controller_Contact::getInstance()->getRecordByTitleProperty(trim($_data['contact_id']));
        if ($contactRecord) {
            $_data['contact_id'] = $contactRecord->id;
        }

        return $_data;
    }
}