<?php
/**
 * Tine 2.0
 *
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class UserManual_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * fix index name
     */
    public function update_0()
    {
        $this->updateSchema('UserManual', array('UserManual_Model_ManualContext'));

        $this->setApplicationVersion('UserManual', '1.1');
    }
}
