<?php

/**
 * Tine 2.0
 *
 * @package     Projects
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Projects_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Projects', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        if (Tinebase_Application::getInstance()->isInstalled(Tasks_Config::APP_NAME)) {
            Projects_Setup_Initialize::applicationInstalled(
                Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME)
            );
        }
        $this->addApplicationUpdate('Projects', '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        try {
            Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME);
        } catch (Tinebase_Exception_NotFound $e) {
            $this->addApplicationUpdate('Projects', '16.2', self::RELEASE016_UPDATE002);
            return;
        }

        $relationCtrl = Tinebase_Relations::getInstance();
        $taskCtrl = Tasks_Controller_Task::getInstance();
        foreach ($relationCtrl->getMultipleRelations(
                    Projects_Model_Project::class,
                    'Sql',
                    Projects_Controller_Project::getInstance()->getAll()->getArrayOfIds(),
                    'sibling',
                    ['TASK']
                ) as $relations) {
            foreach ($relations as $relation) {
                try {
                    $task = $taskCtrl->get($relation->related_id);
                    $task->source = $relation->own_id;
                    $task->source_model = Projects_Model_Project::class;
                    $taskCtrl->update($task);
                } catch (Tinebase_Exception_NotFound $e) {}
                $relationCtrl->deleteByRelIds([$relation->getId()]);
            }
        }
        $this->addApplicationUpdate('Projects', '16.2', self::RELEASE016_UPDATE002);
    }
}
