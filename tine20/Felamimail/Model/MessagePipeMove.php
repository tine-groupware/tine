<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * felamimail model message pipe move config model
 *
 * @package     Felamimail
 * @subpackage  Model
 *
 */
class Felamimail_Model_MessagePipeMove implements Tinebase_BL_ElementInterface, Tinebase_BL_ElementConfigInterface
{
    protected $_config;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * move mail to trash, use configured trash folder of current user
     *
     * @param Tinebase_BL_PipeContext $_context
     * @param Tinebase_BL_DataInterface $_data
     * @return void
     * @throws Tinebase_Exception_NotFound
     * @throws Exception
     */
    public function execute(Tinebase_BL_PipeContext $_context, Tinebase_BL_DataInterface $_data)
    {
        /** @var Felamimail_Model_Message $_data */

        $targetFolder = $this->_config['target']['folder'];
        $account = Felamimail_Controller_Account::getInstance()->get($_data->account_id);
        $folder = Felamimail_Model_MessagePipeConfig::getTargetFolder($account, $targetFolder);

        if (isset($this->_config['addFlags']) && is_array($this->_config['addFlags'])) {
            try {
                Felamimail_Controller_Message_Flags::getInstance()->addFlags($_data, $this->_config['addFlags']);
            } catch (Felamimail_Exception_IMAP $fei) {
                Tinebase_Exception::log($fei);
            }
        }
        Felamimail_Controller_Message_Move::getInstance()->moveMessages($_data, $folder, false);
    }
    
    public function getNewBLElement()
    {
        return $this;
    }

    public function cmp(Tinebase_BL_ElementConfigInterface $_element)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' should not be called');
    }
}
