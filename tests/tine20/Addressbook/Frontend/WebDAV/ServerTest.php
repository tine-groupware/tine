<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Addressbook_Frontend_WebDAV_*
 */
class Addressbook_Frontend_WebDAV_ServerTest extends TestCase
{
    protected \Sabre\DAV\Server $server;
    protected Tinebase_WebDav_Sabre_ResponseMock $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Tinebase_WebDav_Root()), new Tinebase_WebDav_Sabre_SapiMock());
        $this->server->debugExceptions = true;

        $this->response = new Tinebase_WebDav_Sabre_ResponseMock();
        $this->server->httpResponse = $this->response;

        $this->server->addPlugin(
            new \Sabre\DAV\Auth\Plugin(new Tinebase_WebDav_Auth())
        );
        $aclPlugin = new Tinebase_WebDav_Plugin_ACL();
        $aclPlugin->principalCollectionSet = [
            Tinebase_WebDav_PrincipalBackend::PREFIX_USERS,
            Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS,
            Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS
        ];
        $aclPlugin->principalSearchPropertySet = array(
            '{DAV:}displayname' => 'Display name',
            '{' . \Sabre\DAV\Server::NS_SABREDAV . '}email-address' => 'Email address',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}email-address-set' => 'Email addresses',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name' => 'First name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name' => 'Last name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set' => 'Calendar user address set',
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type' => 'Calendar user type'
        );
        $this->server->addPlugin($aclPlugin);
        $this->server->addPlugin(new \Sabre\CardDAV\Plugin());
    }

    public function testDelete()
    {
        $container = $this->_getPersonalContainer(Addressbook_Model_Contact::class);
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'n_fn' => 'unit test',
            'container_id' => $container
        ]));

        $request = new Sabre\HTTP\Request('DELETE', '/addressbooks/' . Tinebase_Core::getUser()->contact_id . '/' . $container->getId() . '/' . $contact->getId() . '.vcf');
        $this->server->httpRequest = $request;
        $this->server->exec();

        $this->assertSame(204, $this->response->getStatus());
        $this->expectException(Tinebase_Exception_NotFound::class);
        Addressbook_Controller_Contact::getInstance()->get($contact->getId());
    }
}