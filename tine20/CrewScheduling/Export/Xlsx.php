<?php
/**
 * Xlsx export generation class
 *
 * Export into specific xlsx template
 *
 * @package     CrewScheduling
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CrewScheduling Xlsx generation class
 *
 * @package     CrewScheduling
 * @subpackage  Export
 *
 */
class CrewScheduling_Export_Xlsx extends Tinebase_Export_Xls
{
    use Calendar_Export_GenericTrait;

    /**
     * @var string
     */
    protected $_defaultExportname = 'crew_scheduling_xlsx';

    /**
     * @var bool
     */
    protected $_sendEmail = false;

    /** @var null|Tinebase_Notification_Interface */
    protected $_smtpBackend = null;

    /**
     * @var null|Tinebase_Record_RecordSet
     */
    protected $_roles = null;

    /**
     * @var array
     */
    protected $_attendeeContactId = array();


    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    /** @phpstan-ignore-next-line */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, ?Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        if (isset($_additionalOptions['sendEmail'])) {
            $this->_sendEmail = (bool)$_additionalOptions['sendEmail'];
            if (true === $this->_sendEmail) {
                $this->_smtpBackend = Tinebase_Notification_Factory::getBackend(Tinebase_Notification_Factory::SMTP);
            }
        }

        $allRoles = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll('order');
        $roleKeys = $allRoles->key;
        if (isset($_additionalOptions['roles']) && is_array($_additionalOptions['roles']) &&
            !empty($_additionalOptions['roles'])) {
            $roleKeys = array_intersect($_additionalOptions['roles'], $roleKeys);
            array_walk($roleKeys, function (&$val) { $val = preg_quote($val, '/'); });
            $allRoles = $allRoles->filter('key', '/^(' . join('|', $roleKeys) . ')$/', true);
        }
        if ($allRoles->count() < 1) {
            throw new Tinebase_Exception_UnexpectedValue('no valid crew scheduling roles available');
        }
        $this->_roles = $allRoles;

        $this->init($_filter, $_controller, $_additionalOptions);
    }

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        return array_merge(parent::_getTwigContext($context), array(
            'calendar'      => array(
                'from'          => $this->_from,
                'until'         => $this->_until,
            )
        ));
    }

    /**
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws PHPExcel_Exception
     */
    protected function _onBeforeExportRecords()
    {
        $translation = Tinebase_Translation::getTranslation('Calendar');

        // clean up calendar generic trait stuff
        $this->_groupByProcessor = null;
        $this->_groupByProperty = null;

        // do parent stuff, but check that _dumpRecords gets set to false!
        $this->_dumpRecords = true;
        parent::_onBeforeExportRecords();
        if (false !== $this->_dumpRecords) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ .
                    'template needs to contain content row ${ROW}');
            throw new Tinebase_Exception_UnexpectedValue('template needs to contain content row ${ROW}');
        }
        if (count($this->_cloneRow) < 2) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ .
                    'template needs to contain at least two columns in ${ROW}');
            throw new Tinebase_Exception_UnexpectedValue('template needs to contain at least two columns in ${ROW}');
        }

        if (null === ($block = $this->_findCell('${HEAD}'))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ .
                    'template needs to contain header row ${HEAD}');
            throw new Tinebase_Exception_UnexpectedValue('template needs to contain header row ${HEAD}');
        }
        $headColumn = $block->getColumn();
        $headRow = $block->getRow();

        if (null === ($block = $this->_findCell('${/HEAD}'))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ .
                    'template needs to contain header row ${/HEAD}');
            throw new Tinebase_Exception_UnexpectedValue('template needs to contain header row ${/HEAD}');
        }
        $headEndColumn = $block->getColumn();

        if ($block->getRow() !== $headRow) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . ' ' . __LINE__ .
                    ' block tags need to be in the same row');
            throw new Tinebase_Exception_UnexpectedValue('block tags need to be in the same row');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' found head block...');

        $sheet = $this->_spreadsheet->getActiveSheet();
        /** @var  $rowIterator */
        $rowIterator = $sheet->getRowIterator($headRow);
        $row = $rowIterator->current();
        $cellIterator = $row->getCellIterator($headColumn, $headEndColumn);

        $replace = array('${HEAD}', '${/HEAD}');
        $counter = 0;
        $countRoles = count($this->_roles);
        $lastColumn = 'A';
        /** @var PhpOffice\PhpSpreadsheet\Cell\ $cell */
        foreach($cellIterator as $cell) {
            if ($counter > $countRoles) {
                $cell->setValue();
                $cell->setXfIndex();
            } else {
                if ($counter > 0) {
                    $cell->setValue($this->_roles->getByIndex($counter - 1)->getTitle());
                } else {
                    $cell->setValue(str_replace($replace, '', $cell->getValue()));
                }
            }
            $lastColumn = $cell->getColumn();
            ++$counter;
        }

        $i = 1;
        $lastColumn = ord($lastColumn);
        while ($counter < $countRoles + 1) {
            $newCell = $sheet->getCell(chr($lastColumn + $i) . $headRow);
            $newCell->setValue($this->_roles->getByIndex($counter - 1)->getTitle());
            $newCell->setXfIndex($cell->getXfIndex());
            ++$i;
            ++$counter;
        }

        $i = 1;
        /** @var CrewScheduling_Model_SchedulingRole $role */
        foreach ($this->_roles as $role) {
            if (!isset($this->_cloneRow[$i])) {
                $this->_cloneRow[$i] = $this->_cloneRow[$i - 1];
                $this->_cloneRow[$i]['column'] = chr(ord($this->_cloneRow[$i]['column']) + 1);
            }
            $this->_cloneRow[$i]['value'] = preg_replace_callback('/(\${twig:[^\}]*record\.)([^\.\}]+)\./s',
                function ($_matches) use ($role) {
                    if ($_matches[2] === 'event') {
                        return $_matches[0];
                    }
                    return $_matches[1] . $role->key . '.';
                }, $this->_cloneRow[$i]['value']);
            ++$i;
        }

        $dynamicProperties = $this->_roles->key;
        $dynamicProperties[] = 'event';
        $dynamicProperties[] = 'id';
        $dynamicRecord = new Tinebase_Record_Simple(array(), true);
        $dynamicRecord->setValidators(array_fill_keys($dynamicProperties, true));
        $newRecords = new Tinebase_Record_RecordSet(Tinebase_Record_Simple::class, array());

        /** @var Calendar_Model_Event $event */
        foreach ($this->_records as $event) {
            $newRow = clone $dynamicRecord;
            if ($event->status == 'CANCELLED'){
                $event->summary =  $this->strikeText($event->summary) . '  (' . $translation->_('Canceled') . ')';
            };
            $newRow->event = $event;
            /** @var CrewScheduling_Model_SchedulingRole $role */
            foreach ($this->_roles as $role) {
                $attendees = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, array());
                foreach ($event->attendee as $attender) {
                    foreach ($attender->crewscheduling_roles as $csRole) {
                        if ($csRole->role->key == $role->key) {
                            $attendees->addRecord($attender);
                            $this->_attendeeContactId = array_merge($this->_attendeeContactId, [$attender->user_id]);
                        }
                    }
                }
                $newRow->{$role->key} = $attendees;
            }
            $newRecords->addRecord($newRow);
        }
        $this->_attendeeContactId = array_unique($this->_attendeeContactId);
        $this->_records = $newRecords;

        $this->_spreadsheet->getActiveSheet()->getPageSetup()->clearPrintArea();
        $this->_spreadsheet->getActiveSheet()->setBreak( chr(ord('A') + 7) . 1, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::BREAK_COLUMN);
    }

    public function _getTwigSource()
    {
        $source = rtrim(parent::_getTwigSource(), ']');

        $roleTwigs = array();
        foreach ($this->_twigMapping as $twig) {
            if (preg_match('/\$\{twig:(.*record\.([^\.]+)\..+)\}/s', $twig, $matches)) {
                if ($matches[2] !== 'event') {
                    $roleTwigs[] = $matches[1];
                }
            }
        }

        $roleTwigs = array_unique($roleTwigs);

        foreach ($roleTwigs as $roleTwig) {
            /** @var CrewScheduling_Model_SchedulingRole $role */
            foreach ($this->_roles as $role) {
                $roleTwig = preg_replace_callback('/^(.*record\.)[^\.]+(\..*)$/s',
                    function ($_matches) use ($role) {
                        return $_matches[1] . $role->key . $_matches[2];
                    }, $roleTwig);
                $source .= ',{{' . $roleTwig . '}}';
                $this->_twigMapping[] = '${twig:' . $roleTwig . '}';
            }
        }

        return $source . ']';
    }

    /**
     * output result
     *
     * @param string $_target
     */
    public function save($_target = null)
    {
        $this->write($_target);
    }

    /**
     * output result
     *
     * @param string $_target
     */
    public function write($_target = null)
    {
        if (null === $_target) {
            if (false === $this->_sendEmail) {
                $target = 'php://output';
            } else {
                $target = Tinebase_TempFile::getTempPath();
            }
        } else {
            $target = $_target;
        }

        parent::write($target);

        if (null === $_target && true === $this->_sendEmail) {
            $attachment = file_get_contents($target);
            $this->_sendMail($attachment);
            echo $attachment;
        }
    }

    protected function _sendMail($_attachement)
    {

        // FIXME !!! tests/tine20/CrewScheduling/Export/XlsxTest.php:119 adjust test once implemented (again?!)

        // NOTE: role has groups (& operator)
        //       groups (&operator) can be overridden per eventType cfg

        // @TODO $this->_roles -> foreach ->
        //  evaluate \CrewScheduling_Model_SchedulingRole::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS
        //           \CrewScheduling_Model_SchedulingRole::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS_OPERATOR
        //

//        foreach (Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($members)) as $contact) {
//            $this->_smtpBackend->send(Tinebase_Core::getUser(), $contact, 'Dienstplan', 'Dienstplan', null, $_attachement);
//        }
    }

    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        parent::_resolveRecords($_records);
    }
}
