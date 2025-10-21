<?php

/**
 * tine Groupware
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class MatrixSynapseIntegrator_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE        => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(
            MatrixSynapseIntegrator_Config::APP_NAME,
            '19.0',
            self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        MatrixSynapseIntegrator_Setup_Initialize::initializeCustomFields();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setMatrixIdInContacts();

        $this->addApplicationUpdate(
            MatrixSynapseIntegrator_Config::APP_NAME,
            '19.1',
            self::RELEASE019_UPDATE001);
    }
}
