<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
class CrewScheduling_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 1.1
     *
     * @return void
     * @throws \Setup_Exception_NotFound
     */
    public function update_0()
    {
        $this->updateSchema('CrewScheduling', [
            CrewScheduling_Model_SchedulingRole::class
        ]);

        // rename cf's
        $keyMap = [
            'ministranten'       => 'MIN',
            'lektoren'           => 'LEK',
            'kommunionshelfer'   => 'KOM',
            'organisten'         => 'ORG',
            'kuester'            => 'KUE',
            'kirchbusfahrer'     => 'KIR'

        ];
        $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication('Calendar', Calendar_Model_Event::class);

        /* @param $customfieldDefinition Tinebase_Model_CustomField_Config  */
        foreach($cfs as $customfieldDefinition) {
            if (preg_match('/^anzahl_(.*)/', $customfieldDefinition->name, $matches)) {
                $customfieldDefinition->name = 'minCount_' . $keyMap[$matches[1]];

                Tinebase_CustomField::getInstance()->updateCustomField($customfieldDefinition);
            }
        }

        // fill roles with key, color, ...
        foreach(CrewScheduling_Controller_SchedulingRole::getInstance()->getAll() as $oldRole) {
            $roleName = $oldRole->name;
            $csRole = array_filter(CrewScheduling_Setup_Initialize::$csRoles, function ($csRole) use ($roleName) {
                return preg_match('/^' . substr($csRole['name'], 0, 5) . '.*/', $roleName);
            });
            if (count($csRole)) {
                foreach (array_pop($csRole) as $key => $value) {
                    $oldRole->{$key} = $value;
                }
                CrewScheduling_Controller_SchedulingRole::getInstance()->update($oldRole);
            }
        }

        $this->setApplicationVersion('CrewScheduling', '1.1');
    }

    /**
     * update to 1.2
     *
     * @return void
     * @throws \Setup_Exception_NotFound
     */
    public function update_1()
    {
        $this->updateSchema('CrewScheduling', [
            CrewScheduling_Model_SchedulingRole::class
        ]);

        $cel = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll()->filter('key', 'CEL')->getFirstRecord();
        if ($cel) {
            $cel->defaultMaxCount = 1;
            CrewScheduling_Controller_SchedulingRole::getInstance()->update($cel);
        }

        $this->setApplicationVersion('CrewScheduling', '1.2');
    }

    /**
     * update to 1.3
     *
     * @return void
     * @throws \Setup_Exception_NotFound
     */
    public function update_2()
    {
        $this->updateSchema('CrewScheduling', [
            CrewScheduling_Model_SchedulingRole::class
        ]);
        $this->setApplicationVersion('CrewScheduling', '1.3');
    }

    /**
     * update to 1.4
     *
     * @return void
     * @throws \Setup_Exception_NotFound
     */
    public function update_3()
    {
        $kom = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll()->filter('key', 'KOM')->getFirstRecord();
        if ($kom) {
            $kom->name = 'Kommunionhelfer';
            $kom->calendarRoleName = 'Kommunionhelfer';
            CrewScheduling_Controller_SchedulingRole::getInstance()->update($kom);
        }

        $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(Tinebase_Application::getInstance()->getApplicationByName('Calendar'), 'minCount_KOM', Calendar_Model_Event::class);
        $cf->definition->label = 'Minimale Anzahl Kommunionhelfer';

        Tinebase_CustomField::getInstance()->updateCustomField($cf);

        $attendeeRoles = Calendar_Config::getInstance()->get(Calendar_Config::ATTENDEE_ROLES);
        foreach ($attendeeRoles->records as $attendeeRole) {
            if ($attendeeRole->id == 'KOM') {
                $attendeeRole->value = 'Kommunionhelfer';
            }
        }
        Calendar_Config::getInstance()->set(Calendar_Config::ATTENDEE_ROLES, $attendeeRoles->toArray());

        $this->setApplicationVersion('CrewScheduling', '1.4');
    }

    /**
     * update to 1.5
     *
     * @return void
     */
    public function update_4()
    {
        $this->updateSchema('CrewScheduling', [
            CrewScheduling_Model_SchedulingRole::class
        ]);

        $this->setApplicationVersion('CrewScheduling', '1.5');
    }

    /**
     * update to 1.6
     * import the export definitions and templates
     *
     * @return void
     */
    public function update_5()
    {
        $this->updateSchema('CrewScheduling', [
            CrewScheduling_Model_SchedulingRole::class
        ]);

        $application = Tinebase_Application::getInstance()->getApplicationByName('CrewScheduling');
        Setup_Controller::getInstance()->createImportExportDefinitions($application, Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('CrewScheduling', '1.6');
    }

    /**
     * update to 1.7
     * import the export definitions and templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_6()
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName('CrewScheduling');
        
        Setup_Controller::getInstance()->createImportExportDefinitions($application, Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('CrewScheduling', '1.7');
    }

    /**
     * update to 1.8
     * import the export definitions and templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_7()
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName('CrewScheduling');

        Setup_Controller::getInstance()->createImportExportDefinitions($application, Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('CrewScheduling', '1.8');
    }

    /**
     * update to 1.9
     * import the export definitions and templates
     *
     * @return void
     * @throws \Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function update_8()
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName('CrewScheduling');

        Setup_Controller::getInstance()->createImportExportDefinitions($application, Tinebase_Core::isReplicationSlave());

        $this->setApplicationVersion('CrewScheduling', '1.9');
    }

    /**
     * update to 1.10
     * add siteFilter to site record pickers
     *
     * @return void
     */
    public function update_9()
    {
        if (Tinebase_Core::isReplicationMaster()) {
            $siteCf = Tinebase_CustomField_Config::getInstance()->search(new Tinebase_Model_CustomField_ConfigFilter([
                ['field' => 'name', 'operator' => 'equals', 'value' => 'site']
            ]))->getFirstRecord();
            if ($siteCf) {
                $siteCf->definition['recordConfig']['additionalFilterSpec'] = [
                    'config' => [
                        'name' => 'siteFilter',
                        'appName' => 'Addressbook'
                    ]
                ];
                Admin_Controller_Customfield::getInstance()->update($siteCf);
            }
        }

        $this->setApplicationVersion('CrewScheduling', '1.10');
    }

    /**
     * update to 1.11
     * data cleanup
     *
     * @return void
     */
    public function update_10()
    {
        $this->setApplicationVersion('CrewScheduling', '1.11');
    }

    /**
     * update to 1.12
     *
     * @return void
     */
    public function update_11()
    {
        $this->getDb()->update(SQL_TABLE_PREFIX . 'scheduling_role', ['deleted_time' => '1970-01-01 00:00:00'],
            $this->getDb()->quoteIdentifier('deleted_time') . ' IS NULL');

        $this->setApplicationVersion('CrewScheduling', '1.12');
    }

    /**
     * update to 1.13
     *
     * @return void
     */
    public function update_12()
    {
        $this->updateSchema('CrewScheduling', [
            CrewScheduling_Model_SchedulingRole::class
        ]);

        $attendeeKeyField = Calendar_Config::getInstance()->{Calendar_Config::ATTENDEE_ROLES};
        foreach ($attendeeKeyField->records as $record) {
            $record->system = true;
        }
        Calendar_Config::getInstance()->{Calendar_Config::ATTENDEE_ROLES} = $attendeeKeyField;

        $this->setApplicationVersion('CrewScheduling', '1.13');
    }
}
