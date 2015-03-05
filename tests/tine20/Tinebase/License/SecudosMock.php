<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Tine 2.0 Secudos License class MOCK
 *
 * @package     Tinebase
 */
class Tinebase_License_SecudosMock extends Tinebase_License_Secudos
{
    /**
     * the constructor
     */
    public function __construct()
    {
        parent::__construct();

        self::$applianceType = null;
        $this->_modelFilename = dirname(__FILE__) . '/model';
    }
}
