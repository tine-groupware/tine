<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * import data from db
 *
 * @package     Tinebase
 * @subpackage  Import
 */
abstract class Tinebase_Import_Db_Abstract
{
    protected Zend_Db_Adapter_Abstract $_importDb;
    protected ?string $_mainTableName = null;
    protected bool $_duplicateCheck = true;
    protected bool $_mergeExistingRecords = true;
    protected array $_descriptionFields = [];
    protected ?string $_importFilter = null;
    protected ?string $_importOrder = null;
    protected int $_initialPageNumber = 0;

    public function __construct(?Zend_Db_Adapter_Abstract $db = null)
    {
        $this->_importDb = $db ?: Tinebase_Core::getDb();
    }

    /**
     * @return array of imported IDs
     */
    public function import(): array
    {
        $count = 0;
        $skipcount = 0;
        $failcount = 0;
        $pageNumber = $this->_initialPageNumber;
        $pageCount = 100;
        $importedIds = [];
        do {
            $select = $this->_getSelect(++$pageNumber, $pageCount);
            $stmt = $select->query();
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            $stmt->closeCursor();
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' fetched ' . count($rows) . ' rows  / pagenumber: ' . $pageNumber);

            foreach ($rows as $row) {
                try {
                    if ($record = $this->_importRecord($row)) {
                        $count++;
                        $importedIds[] = $record->getId();
                    } else {
                        $failcount++;
                    }
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not import ' . $this->_mainTableName . ' record: ' . $e);
                    $failcount++;
                }
            }
        } while (count($rows) >= $pageCount);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Imported ' . $count . ' records from ' . $this->_mainTableName
                . ' (failcount: ' . $failcount . ' | skipcount: ' . $skipcount . ')');
        }

        $this->_onAfterImport();

        return $importedIds;
    }

    protected function _getSelect(int $pageNumber, int $pageCount): Zend_Db_Select
    {
        $select = $this->_importDb->select()->from($this->_mainTableName)->limitPage($pageNumber, $pageCount);
        if ($this->_importFilter) {
            $select->where($this->_importFilter);
        }
        if ($this->_importOrder) {
            $select->order($this->_importOrder);
        }
        return $select;
    }

    protected function _importRecord($row)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Importing data from table ' . $this->_mainTableName . ': ' . print_r($row, true));
        }

        $recordToImport = $this->_getRecord($row);

        $controller = $this->_getController();
        try {
            $record = $controller->get($recordToImport->getId());
            if ($this->_mergeExistingRecords) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Merge with existing record');
                }
                $record->merge($recordToImport);
                $record = $controller->update($record);
                $this->_onAfterImportRecord($record, $row);
            } else if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Ignore existing record');
            }
        } catch (Tinebase_Exception_NotFound) {
            $record = $controller->create($recordToImport, $this->_duplicateCheck);
            $this->_onAfterImportRecord($record, $row);
        }

        return $record;
    }

    abstract protected function _getRecord($row): Tinebase_Record_Interface;
    abstract protected function _getController(): Tinebase_Controller_Record_Abstract;

    protected function _onAfterImportRecord(Tinebase_Record_Interface $record, array $row): void
    {
    }

    protected function _onAfterImport(): void
    {
    }

    protected function _getDescription(array $row): string
    {
        $note = '';
        foreach ($this->_descriptionFields as $field) {
            if (! empty($row[$field])) {
                $note .= "$field: " . $row[$field] . "\n";
            }
        }

        return $note;
    }

    protected function _parseTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (preg_match('/^([0-9][0-9]).([0-9][0-9])/', $value, $matches)) {
            if ($matches[1] >= 0 && $matches[1] < 24 && $matches[2] >=0 && $matches[2] < 59) {
                return $matches[1] . ':' . $matches[2];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    protected function _parseFloat(?string $value): ?float
    {
        if ($value !== 0 && empty($value)) {
            return null;
        }

        return (float) preg_replace(['/[\.a-z ]*/i', '/,/'],['','.'], $value);
    }

    /**
     * get data from a table
     *
     * @param string $table
     * @param string $fk
     * @param string $id
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    protected function _getAdditionalData(string $table, string $fk, string $id): array
    {
        $stmt = $this->_importDb->select()->from($table)->where($fk . ' = ?', $id)->query();
        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $stmt->closeCursor();
        return $result;
    }
}
