<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * custom event hook for \Tinebase_Group_LdapTest::testAddGroupMemberEvent
 *
 * @package     Tinebase
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Tinebase_Controller_CustomEventHook
{
    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    public function handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case Tinebase_Event_AddGroupMember::class:
                echo 'Handled event Tinebase_Event_AddGroupMember';
                break;
        }
    }
}
