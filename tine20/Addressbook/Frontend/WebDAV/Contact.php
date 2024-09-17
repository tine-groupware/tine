<?php
use \Sabre\DAV;

/**
 * class to handle a single vcard
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle a single vcard
 *
 * This class handles the creation, update and deletion of vcards
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_WebDAV_Contact extends Sabre\DAV\File implements Sabre\CardDAV\ICard, Sabre\DAVACL\IACL
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    /**
     * @var Addressbook_Model_Contact
     */
    protected $_contact;
    
    /**
     * holds the vcard returned to the client
     * 
     * @var string
     */
    protected $_vcard;
    
    /**
     * @var Addressbook_Convert_Contact_VCard_Abstract
     */
    protected $_converter;

    /**
     * Constructor
     *
     * @param Tinebase_Model_Container $_container
     * @param  string|Addressbook_Model_Contact $_contact the id of a contact or the contact itself
     */
    public function __construct(Tinebase_Model_Container $_container, $_contact = null) 
    {
        $this->_container = $_container;
        $this->_contact   = $_contact;

        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $this->_converter = Addressbook_Convert_Contact_VCard_Factory::factory($backend, $version);
    }

    /**
     * this function creates a Addressbook_Model_Contact and stores it in the database
     *
     * @todo the header handling does not belong here. It should be moved to the DAV_Server class when supported
     *
     * @param  Tinebase_Model_Container $container
     * @param  string $name
     * @param  resource|string $vcardData
     * @return Addressbook_Frontend_WebDAV_Contact
     * @throws DAV\Exception\Forbidden
     */
    public static function create(Tinebase_Model_Container $container, $name, $vcardData)
    {
        list($backend, $version) = Addressbook_Convert_Contact_VCard_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
        
        $converter = Addressbook_Convert_Contact_VCard_Factory::factory($backend, $version);

        self::checkWritePermission($converter);

        $contact = $converter->toTine20Model($vcardData);
        $contact->container_id = $container->getId();
        
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        $id = strlen($id) > 40 ? sha1($id) : $id;
        $contact->setId($id);
        
        try {
            $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            throw new DAV\Exception\Forbidden('Access denied');
        }
        
        $card = new self($container, $contact);
        
        return $card;
    }

    /**
     * Deletes the card
     *
     * @throws DAV\Exception\Forbidden
     * @throws Exception
     * @see Calendar_Frontend_WebDAV_Event::delete()
     */
    public function delete() 
    {
        self::checkWritePermission($this->_converter);

        // when a move occurs, thunderbird first sends to delete command and immediately a put command
        // we must delay the delete command, otherwise the put command fails
        sleep(1);
        
        // (re) fetch contact as tree move does not refresh src node before delete
        // check if we are still in the same container, if not -> it is a MOVE
        try {
            $contact = Addressbook_Controller_Contact::getInstance()->get($this->_contact);
            if ($contact->getIdFromProperty('container_id') === $this->_container->getId()) {
                Addressbook_Controller_Contact::getInstance()->delete($contact);
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            throw new DAV\Exception\Forbidden('Access denied');
        }
    }
    
    /**
     * Returns the VCard-formatted object 
     * 
     * @return resource
     */
    public function get() 
    {
        $s = fopen('php://temp','r+');
        fwrite($s, $this->_getVCard());
        rewind($s);
        
        return $s;
    }
    
    /**
     * Returns the uri for this object 
     * 
     * @return string 
     */
    public function getName() 
    {
        return $this->getRecord()->getId() . '.vcf';
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @todo add real owner
     * @return string|null
     */
    public function getOwner() 
    {
        return null;
        //return $this->addressBookInfo['principaluri'];
    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo add real group
     * @return string|null 
     */
    public function getGroup() 
    {
        return null;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are 
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to 
     *      be updated. 
     * 
     * @return array|null
     */
    public function getACL() 
    {
        return (new Addressbook_Frontend_WebDAV_Container($this->_container))->getACL();
    }
    
    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType()
    {
        return 'text/vcard';
    }
    
    /**
     * Returns an ETag for this object
     *
     * @return string
     */
    public function getETag() 
    {
        return '"' . md5($this->getRecord()->getId() . $this->getLastModified()) . '"';
    }

    public function getLastModified() 
    {
        return ($this->getRecord()->last_modified_time instanceof Tinebase_DateTime) ? $this->getRecord()->last_modified_time->getTimestamp() :
               (($this->getRecord()->creation_time instanceof Tinebase_DateTime) ? $this->getRecord()->creation_time->getTimestamp() : null);
    }
    
    /**
     * Returns the size of the vcard in bytes
     *
     * @return int
     */
    public function getSize() 
    {
        return strlen((string)$this->_getVCard());
    }

    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @return string
     * @throws DAV\Exception\Forbidden
     * @throws DAV\Exception\NotFound
     * @throws DAV\Exception\PreconditionFailed
     */
    public function put($cardData)
    {
        self::checkWritePermission($this->_converter);

        $contact = $this->_converter->toTine20Model($cardData, $this->getRecord(), array(
            Addressbook_Convert_Contact_VCard_Abstract::OPTION_USE_SERVER_MODLOG => true,
        ));
        
        try {
            $this->_contact = Addressbook_Controller_Contact::getInstance()->update($contact, false);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
            throw new Sabre\DAV\Exception\NotFound('Contact not found');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            throw new DAV\Exception\Forbidden('Access denied');
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tesg->getMessage());
            throw new DAV\Exception\PreconditionFailed($tesg->getMessage());
        }
        $this->_vcard = null;
        
        return $this->getETag();
    }

    public static function checkWritePermission(Addressbook_Convert_Contact_VCard_Abstract $converter): void
    {
        $configReadonlyPolicy = Addressbook_Config::getInstance()->get(Addressbook_Config::CARDDAV_READONLY_POLICY);
        if ($configReadonlyPolicy === Addressbook_Config::CARDDAV_READONLY_POLICY_UNKNOWN
            && get_class($converter) === Addressbook_Convert_Contact_VCard_Generic::class)
        {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Write access by generic client not allowed.'
                    . ' See Addressbook_Convert_Contact_VCard_Factory for supported clients.');
            }
            throw new DAV\Exception\Forbidden('Write access denied for unknown client');
        } else if ($configReadonlyPolicy === Addressbook_Config::CARDDAV_READONLY_POLICY_ALWAYS) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Write access not allowed.'
                    . ' See Addressbook_Config::CARDDAV_READONLY_POLICY');
            }
            throw new DAV\Exception\Forbidden('Write access denied');
        }
    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @throws DAV\Exception\MethodNotAllowed
     */
    public function setACL(array $acl) 
    {
        throw new DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');
    }

    /**
     * return Addressbook_Model_Contact and convert contact id to model if needed
     *
     * @return Addressbook_Model_Contact
     * @throws DAV\Exception\Forbidden
     */
    public function getRecord()
    {
        if (! $this->_contact instanceof Addressbook_Model_Contact) {
            $id = ($pos = strpos($this->_contact, '.')) === false ? $this->_contact : substr($this->_contact, 0, $pos);
            $id = strlen((string)$id) > 40 ? sha1($id) : $id;
            
            try {
                $this->_contact = Addressbook_Controller_Contact::getInstance()->get($id);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
                throw new DAV\Exception\Forbidden('Access denied');
            }
        }
        
        return $this->_contact;
    }
    
    /**
     * return vcard and convert Addressbook_Model_Contact to vcard if needed
     * 
     * @return string
     */
    protected function _getVCard()
    {
        if ($this->_vcard == null) {
            $this->_vcard = $this->_converter->fromTine20Model($this->getRecord());
        }
        
        return $this->_vcard->serialize();
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }
}
