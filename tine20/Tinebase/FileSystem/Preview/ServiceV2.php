<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Preview_ServiceV2 extends Tinebase_FileSystem_Preview_ServiceV1
{
    protected $_networkAdapter;

    public function __construct($networkAdapter)
    {
        parent::__construct();
        $this->_networkAdapter = $networkAdapter;
    }

    /**
     * @param boolean $_synchronRequest
     * @return Zend_Http_Client
     */
    protected function _getHttpClient($_synchronRequest)
    {
        return $this->_networkAdapter->getHttpsClient(array('timeout' => ($_synchronRequest ? 10 : 300)));
    }
}
