<?php
/**
 * Tine 2.0
 *
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class UserManual_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * increase title column length
     */
    public function update_1()
    {
        $this->updateSchema('UserManual', array('UserManual_Model_ManualPage'));

        $this->setApplicationVersion('UserManual', '0.2');
    }

    /**
     * was: re-import user manual pages / we no longer ship the manual in the package
     */
    public function update_2()
    {
        $this->setApplicationVersion('UserManual', '1.0');
    }
}
