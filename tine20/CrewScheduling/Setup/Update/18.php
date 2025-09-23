<?php

/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class CrewScheduling_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE018_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE018_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE018_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate('CrewScheduling', '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        // Delete Min Max Count CF
        if (!Tinebase_Core::isReplica()) {
            $cal = Tinebase_Application::getInstance()->getApplicationByName('Calendar');
            $filter = new Tinebase_Model_CustomField_ConfigFilter(array(
                    array('field' => 'name', 'operator' => 'contains', 'value' => 'minCount_'),
                    array('field' => 'application_id', 'operator' => 'equals', 'value' => $cal->getId()),
                    array('field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class),
                )
            );
            $filter->customfieldACLChecks(false);

            foreach (Tinebase_CustomField::getInstance()->searchConfig($filter) as $cFConfig) {
                Tinebase_CustomField::getInstance()->deleteCustomField($cFConfig);
            }

            $filter = new Tinebase_Model_CustomField_ConfigFilter(array(
                    array('field' => 'name', 'operator' => 'contains', 'value' => 'maxCount_'),
                    array('field' => 'application_id', 'operator' => 'equals', 'value' => $cal->getId()),
                    array('field' => 'model', 'operator' => 'equals', 'value' => Calendar_Model_Event::class),
                )
            );
            $filter->customfieldACLChecks(false);

            foreach (Tinebase_CustomField::getInstance()->searchConfig($filter) as $cFConfig) {
                Tinebase_CustomField::getInstance()->deleteCustomField($cFConfig);
            }
        }
        $this->addApplicationUpdate('CrewScheduling', '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002(): void
    {
        CrewScheduling_Setup_Initialize::createSystemCustomfields();
        $this->addApplicationUpdate('CrewScheduling', '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003(): void
    {
        // disable Addressbook_Model_List schedulingRole cf
        if (!Tinebase_Core::isReplica()) {
            $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
                Addressbook_Config::APP_NAME, 'schedulingRole',
                Addressbook_Model_List::class);
            $cf->xprops('definition')['uiconfig']['disabled'] = true;
            Tinebase_CustomField::getInstance()->updateCustomField($cf);
        }
        $this->addApplicationUpdate('CrewScheduling', '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004(): void
    {
        Setup_SchemaTool::updateSchema([
            CrewScheduling_Model_SchedulingRole::class,
        ]);

        $roleCtrl = CrewScheduling_Controller_SchedulingRole::getInstance();
        $oldValue = $roleCtrl->doContainerACLChecks(false);
        $setContainerMethod = new ReflectionMethod(CrewScheduling_Controller_SchedulingRole::class,
            '_setContainer');
        $setContainerMethod->setAccessible(true);

        $updateContainer = null;
        try {
            $updateContainer = Tinebase_Container::getInstance()->createSystemContainer(
                CrewScheduling_Config::APP_NAME,
                CrewScheduling_Model_SchedulingRole::class,
                'Temp Update Container ' . Tinebase_Record_Abstract::generateUID(6)
            );

            if (Tinebase_Core::isReplica()) {
                foreach ($roleCtrl->getAll() as $role) {
                    if ($role->container_id) continue;
                    $role->container_id = $updateContainer->getId();
                    $roleCtrl->getBackend()->update($role);
                }
                $this->applyPrimaryModlogs();
            }

            foreach ($roleCtrl->getAll() as $role) {
                // NOTE: if role does not exist on primary it has the update container -> do plain update
                if ($role->container_id && $role->container_id !== $updateContainer->getId()) continue;

                $setContainerMethod->invoke($roleCtrl, $role);
                $role = $roleCtrl->getBackend()->update($role);

                // create modlog for replication (NOTE: first update can't be done through controller as it can't cope with nullish container_id
                $container = $role->container_id;
                $role->container_id = $updateContainer->getId();
                $role = $roleCtrl->getBackend()->update($role);
                $role->container_id = $container;
                $roleCtrl->update($role);
            }
        } finally {
            $roleCtrl->doContainerACLChecks($oldValue);
            if ($updateContainer) {
                Tinebase_Container::getInstance()->deleteContainer($updateContainer);
            }
        }
        
        $this->addApplicationUpdate('CrewScheduling', '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005(): void
    {
        Setup_SchemaTool::updateSchema([
            CrewScheduling_Model_EventRoleConfig::class,
            CrewScheduling_Model_EventTypeConfig::class,
        ]);
        $this->addApplicationUpdate('CrewScheduling', '18.5', self::RELEASE018_UPDATE005);
    }

    public function update006(): void
    {
        Setup_SchemaTool::updateSchema([
            CrewScheduling_Model_Poll::class,
            CrewScheduling_Model_PollParticipant::class,
            CrewScheduling_Model_PollReply::class,
        ]);
        $this->addApplicationUpdate('CrewScheduling', '18.6', self::RELEASE018_UPDATE006);
    }
}
