<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

class Tinebase_Log_Writer_Db extends Zend_Log_Writer_Db
{
    /**
     * @var Tinebase_Log_Formatter_Db $_formatter
     */
    protected $_formatter;

    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     * @throws Zend_Log_Exception
     */
    protected function _write($event)
    {
        $eventData = $this->_formatter->format($event);
        try {
            parent::_write($eventData);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not write event: '
                    . $zdse->getMessage());
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                    . print_r($eventData, true));
            }
        }
    }

    /**
     * Set a new formatter for this writer
     *
     * @param  Zend_Log_Formatter_Interface $formatter
     * @return Zend_Log_Writer_Abstract
     */
    public function setFormatter(Zend_Log_Formatter_Interface $formatter)
    {
        $this->_formatter = $formatter;
        return $this;
    }
}
