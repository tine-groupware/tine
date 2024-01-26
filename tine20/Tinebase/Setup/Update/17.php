<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class Tinebase_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE017_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE017_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
       self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
           self::RELEASE017_UPDATE002          => [
               self::CLASS_CONST                   => self::class,
               self::FUNCTION_CONST                => 'update002',
           ],
           self::RELEASE017_UPDATE005          => [
               self::CLASS_CONST                   => self::class,
               self::FUNCTION_CONST                => 'update005',
           ],
        ],
        self::PRIO_TINEBASE_STRUCTURE   => [
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE017_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        (self::PRIO_NORMAL_APP_UPDATE + 1)=> [
            self::RELEASE017_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_FileObject::class,
            Tinebase_Model_Tree_FlySystem::class,
        ]);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_FlySystem::class,
        ]);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_NumberableConfig::class,
        ]);

        $this->getDb()->update(SQL_TABLE_PREFIX . 'numberable', ['bucket' => ''], 'bucket IS NULL');

        $this->_backend->alterCol('numberable', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>bucket</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                    <default/>
                </field>'));

        if ($this->getTableVersion('numberable') < 2) {
            $this->setTableVersion('numberable', 2);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    public function update004()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_EvaluationDimension::class,
            Tinebase_Model_EvaluationDimensionItem::class,
        ]);

        if (null === Tinebase_Controller_EvaluationDimension::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
                    [TMFA::FIELD => Tinebase_Model_EvaluationDimension::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_Model_EvaluationDimension::COST_CENTER],
                ]))->getFirstRecord()) {
            $dimension = new Tinebase_Model_EvaluationDimension([
                Tinebase_Model_EvaluationDimension::FLD_NAME => Tinebase_Model_EvaluationDimension::COST_CENTER,
                Tinebase_Model_EvaluationDimension::FLD_SORTING => 1000,
            ]);

            if ($this->_backend->tableExists('cost_centers')) {
                $items = new Tinebase_Record_RecordSet(Tinebase_Model_EvaluationDimensionItem::class);
                foreach ($this->_db->select()->from(SQL_TABLE_PREFIX . 'cost_centers', ['id', 'number', 'name', 'description'])->query()->fetchAll(Zend_Db::FETCH_ASSOC) as $cc) {
                    $items->addRecord(new Tinebase_Model_EvaluationDimensionItem([
                        Tinebase_Model_EvaluationDimensionItem::ID => $cc['id'],
                        Tinebase_Model_EvaluationDimensionItem::FLD_NAME => ($cc['name'] ?: '-'),
                        Tinebase_Model_EvaluationDimensionItem::FLD_NUMBER => $cc['number'],
                        Tinebase_Model_EvaluationDimensionItem::FLD_DESCRIPTION => $cc['description'],
                    ], true));
                }
                $dimension->{Tinebase_Model_EvaluationDimension::FLD_ITEMS} = $items;
            }

            Tinebase_Controller_EvaluationDimension::getInstance()->create($dimension);
        }

        if (null === Tinebase_Controller_EvaluationDimension::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
                [TMFA::FIELD => Tinebase_Model_EvaluationDimension::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_Model_EvaluationDimension::COST_BEARER],
            ]))->getFirstRecord()) {
            $dimension = new Tinebase_Model_EvaluationDimension([
                Tinebase_Model_EvaluationDimension::FLD_NAME => Tinebase_Model_EvaluationDimension::COST_BEARER,
                Tinebase_Model_EvaluationDimension::FLD_SORTING => 1010,
            ]);

            if ($this->_backend->tableExists('cost_bearers')) {
                $items = new Tinebase_Record_RecordSet(Tinebase_Model_EvaluationDimensionItem::class);
                foreach ($this->_db->select()->from(SQL_TABLE_PREFIX . 'cost_bearers', ['id', 'number', 'name', 'description'])->query()->fetchAll(Zend_Db::FETCH_ASSOC) as $cc) {
                    $items->addRecord(new Tinebase_Model_EvaluationDimensionItem([
                        Tinebase_Model_EvaluationDimensionItem::ID => $cc['id'],
                        Tinebase_Model_EvaluationDimensionItem::FLD_NAME => ($cc['name'] ?: '-'),
                        Tinebase_Model_EvaluationDimensionItem::FLD_NUMBER => $cc['number'],
                        Tinebase_Model_EvaluationDimensionItem::FLD_DESCRIPTION => $cc['description'],
                    ], true));
                }
                $dimension->{Tinebase_Model_EvaluationDimension::FLD_ITEMS} = $items;
            }

            Tinebase_Controller_EvaluationDimension::getInstance()->create($dimension);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }

    public function update005()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('accounts') < 20) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>login_failures</name>
                    <type>text</type>
                    <length>4000</length>
                </field>
            ');
            $this->_backend->alterCol('accounts', $declaration);
            $this->setTableVersion('accounts', 20);
        }

        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'accounts SET login_failures = ' .
            'JSON_OBJECT("JSON-RPC", CAST(login_failures AS INTEGER)) WHERE login_failures IS NOT NULL');
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.5', self::RELEASE017_UPDATE005);
    }

    public function update006()
    {
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostCenter"');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostCenter"');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostUnit"');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostUnit"');

        if ($this->_backend->tableExists('cost_center')) {
            $this->_backend->dropTable('cost_center');
        }
        if ($this->_backend->tableExists('cost_unit')) {
            $this->_backend->dropTable('cost_unit');
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.5', self::RELEASE017_UPDATE005);
    }
}
