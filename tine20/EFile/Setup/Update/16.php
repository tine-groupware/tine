<?php

/**
 * Tine 2.0
 *
 * @package     EFile
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class EFile_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';

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
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('EFile', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        $ids = $this->getDb()->query('select e.id from ' . SQL_TABLE_PREFIX . EFile_Model_FileMetadata::TABLE_NAME
            . ' as e left join ' . SQL_TABLE_PREFIX . Tinebase_Model_Tree_Node::TABLE_NAME
            . ' as n on e.node_id = n.id where n.id is null')->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!empty($ids)) {
            $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . EFile_Model_FileMetadata::TABLE_NAME .
                $this->getDb()->quoteInto(' WHERE id IN (?)', $ids));
        }

        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
            EFile_Model_FileMetadata::class,
            Tinebase_Model_Tree_Node::class,
        ]);
        $this->addApplicationUpdate('EFile', '16.1', self::RELEASE016_UPDATE001);
    }
}
