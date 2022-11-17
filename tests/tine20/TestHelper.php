<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  test root
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */


// only bootstrap once
if (! Tinebase_Core::isRegistered('frameworkInitialized') || Tinebase_Core::get('frameworkInitialized') == false) {
    Tinebase_Session_Abstract::setSessionEnabled('TINE20SESSID');
    TestServer::getInstance()->initFramework();

    TestServer::getInstance()->login();

    // do this after login because we need the current user
    TestServer::getInstance()->setLicense();
    TestServer::getInstance()->initTestUsers();
    TestServer::getInstance()->setTestUserEmail();

    // speed up tests by disabling calendar notifications
    Calendar_Controller_Event::getInstance()->sendNotifications(false);

    Tinebase_ActionQueue::$waitForTransactionManager = false;
}
