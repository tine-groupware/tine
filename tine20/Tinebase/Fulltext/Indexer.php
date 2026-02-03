<?php
/**
 * Tine 2.0
 *
 * class to index text content
 *
 * @package     Tinebase
 * @subpackage  Fulltext
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Tinebase_Fulltext_Indexer
{
    /**
     * @var float|int
     */
    protected $_maxBlobSize = 0;

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Fulltext_Indexer
     */
    private static $_instance = NULL;

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Tinebase_Fulltext_Indexer
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Fulltext_Indexer();
        }

        return self::$_instance;
    }

    /**
     * destroy instance of this class
     */
    public static function destroyInstance()
    {
        self::$_instance = NULL;
    }

    /**
     * constructor
     *
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_NotImplemented
     */
    private function __construct()
    {
        $fulltextConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT);
        if ('Sql' !== $fulltextConfig->{Tinebase_Config::FULLTEXT_BACKEND}) {
            throw new Tinebase_Exception_NotImplemented('only Sql backend is implemented currently');
        }

        $this->_maxBlobSize = self::getMaxBlobSize();
    }

    /**
     * @return int
     */
    public static function getMaxBlobSize(): int
    {
        $maxBlobSize = 0;

        $db = Tinebase_Core::getDb();
        if ($db instanceof Zend_Db_Adapter_Pdo_Mysql) {
            $logFileSize = (int) Tinebase_Core::getDbVariable('innodb_log_file_size', $db);
            if ($logFileSize > 0) {
                $maxBlobSize = round($logFileSize / 10);
            }
            $maxPacketSize = (int) Tinebase_Core::getDbVariable('max_allowed_packet', $db);
            if ($maxPacketSize > 0 && ($maxBlobSize === 0 || $maxPacketSize < $maxBlobSize)) {
                $maxBlobSize = $maxPacketSize;
            }
            // reduce by more chars because we send more than just the blob (ID, ...)
            if ($maxBlobSize > 0) {
                $maxBlobSize = round($maxBlobSize * 0.8);
            }
        }

        return $maxBlobSize;
    }

    /**
     * @param string $_id
     * @param string $_fileName
     *
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function addFileContentsToIndex($_id, $_fileName)
    {
        if (false === ($blob = file_get_contents($_fileName))) {
            throw new Tinebase_Exception_InvalidArgument('could not get file contents of: ' . $_fileName);
        }
        $blob = Tinebase_Core::filterInputForDatabase($blob);

        $blobsize = strlen($blob);
        if (Tinebase_Core::isLogLevel(Tinebase_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Blob size (max): '
            . $blobsize . ' (' . $this->_maxBlobSize . ')');
        if ($this->_maxBlobSize > 0 && $blobsize > $this->_maxBlobSize) {
            if (Tinebase_Core::isLogLevel(Tinebase_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Truncating full text blob for id '
                . $_id . ' to max blob size');
            $blob = substr($blob, 0, $this->_maxBlobSize);
            if (Tinebase_Core::isLogLevel(Tinebase_Log::DEBUG)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Blobsize after reduction: '
                    . strlen($blob));
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__
                . ' Adding index for ' . $_fileName
            );
        }

        $db = Tinebase_Core::getDb();
        $db->delete(SQL_TABLE_PREFIX . 'external_fulltext', $db->quoteInto($db->quoteIdentifier('id') . ' = ?', $_id));
        try {
            $db->insert(SQL_TABLE_PREFIX . 'external_fulltext', array('id' => $_id, 'text_data' => $blob));
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not index file '
                    . $_fileName
                    . ' error: ' . $zdse->getMessage());
            }
        }
    }

    /**
     * @param string|array $_ids
     */
    public function removeFileContentsFromIndex($_ids)
    {
        if (empty($_ids)) {
            return;
        }
        $db = Tinebase_Core::getDb();
        $db->delete(SQL_TABLE_PREFIX . 'external_fulltext', $db->quoteInto($db->quoteIdentifier('id') . ' IN (?)', (array)$_ids));
    }

    public function copyFileContents(string $_sourceId, string $_targetId): void
    {
        $db = Tinebase_Core::getDb();
        $db->query('INSERT INTO ' . SQL_TABLE_PREFIX . 'external_fulltext (`id`, `text_data`) SELECT '
            . $db->quote($_targetId) . ', `text_data` FROM ' . SQL_TABLE_PREFIX . 'external_fulltext WHERE `id` = ' . $db->quote($_sourceId));
    }
}
