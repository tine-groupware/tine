<?php

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to handle generic folders in WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
abstract class Tinebase_WebDav_Collection_Abstract extends DAV\Collection implements DAV\IProperties, DAVACL\IACL
{
    /**
     * the current application object
     * 
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    /**
    * application name
    *
    * @var string
    */
    protected $_applicationName;
    
    protected $_model;
    
    /**
    * app has personal folders
    *
    * @var string
    */
    protected $_hasPersonalFolders = TRUE;
    
    /**
     * 
     * @var Tinebase_Model_FullUser
     */
    protected $_user;
    
    /**
     * @var array
     */
    protected $_pathParts;
    
    /**
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_path         the current path
     */
    public function __construct(/**
     * the current path
     */
    protected $_path)
    {
        $this->_pathParts       = $this->_parsePath($this->_path);
        $this->_applicationName = Tinebase_Helper::array_value(0, explode('_', static::class));
    }
    
    /**
     * Creates a new subdirectory
     *
     * @param  string  $name  name of the new subdirectory
     * @throws Sabre\DAV\Exception\Forbidden
     * @return Tinebase_Model_Container
     */
    public function createDirectory($name) 
    {
        $containerType = $this->_pathParts[1];
        
        if (!in_array($containerType, array(Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Model_Container::TYPE_SHARED))) {
            throw new Sabre\DAV\Exception\Forbidden('Permission denied to create directory');
        }
        
        if ($containerType == Tinebase_Model_Container::TYPE_SHARED &&
            !Tinebase_Core::getUser()->hasRight($this->_getApplication(), Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS)) {
            throw new Sabre\DAV\Exception\Forbidden('Permission denied to create directory');
        }
        
        // is the loginname for personal folders set?
        if ($containerType == Tinebase_Model_Container::TYPE_PERSONAL && count($this->_pathParts) < 3) {
            throw new Sabre\DAV\Exception\Forbidden('Permission denied to create directory');
        }
        
        try {
            Tinebase_Container::getInstance()->getContainerByName($this->_model, $name, $containerType, Tinebase_Core::getUser());
            
            // container exists already => that's bad!
            throw new Sabre\DAV\Exception\Forbidden('Folders exists already');
        } catch (Tinebase_Exception_NotFound) {
            // continue
        }

        if (empty($this->_model)) {
            throw new Tinebase_Exception_Backend('model needs to be known to create a container!');
        }

        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $name,
            'type'           => $containerType,
            'backend'        => 'sql',
            'application_id' => $this->_getApplication()->getId(),
            'model'          => $this->_model,
        )));
        
        return $container;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
     */
    public function getChild($_name)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' name: ' . $_name . ' path parts: ' . count($this->_pathParts));
    
        switch (count($this->_pathParts)) {
            # path == ApplicationName
            # return personal and shared folder
            case 1:
                if (!in_array($_name, array(Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Model_Container::TYPE_SHARED, Tinebase_FileSystem::FOLDER_TYPE_RECORDS))) {
                    throw new Sabre\DAV\Exception\NotFound('Directory not found');
                }
                
                $className = $this->_applicationName . '_Frontend_WebDAV';
                return new $className($this->_path . '/' . $_name);
            
            # path == ApplicationName/{personal|shared|records}
            # list container
            case 2:
                if ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_SHARED) {
                    try {
                        $container = $_name instanceof Tinebase_Model_Container ? $_name : Tinebase_Container::getInstance()->getContainerByName($this->_model, $_name, Tinebase_Model_Container::TYPE_SHARED);
                    } catch (Tinebase_Exception_NotFound) {
                        throw new Sabre\DAV\Exception\NotFound('Directory not found');
                    }
                    if (!Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_READ) || !Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_SYNC)) {
                        throw new Sabre\DAV\Exception\NotFound('Directory not found');
                    }
                    
                    $objectClass = $this->_applicationName . '_Frontend_WebDAV_Container';
                    
                    return new $objectClass($container);
                    
                } elseif ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_PERSONAL) {
                    if ($_name != Tinebase_Core::getUser()->accountLoginName && $_name != 'currentUser') {
                        throw new Sabre\DAV\Exception\NotFound('Child not found');
                    }
                    
                    $className = $this->_applicationName . '_Frontend_WebDAV';
                    
                    return new $className($this->_path . '/' . $_name);
                    
                } elseif ($this->_pathParts[1] == Tinebase_FileSystem::FOLDER_TYPE_RECORDS) {
                    $className = 'Tinebase_Frontend_WebDAV_RecordCollection';
                    
                    return new $className($this->_path . '/' . $_name);
                }
                
                break;
                
            # path == Applicationname/personal/accountLoginName
            # return personal folders
            case 3:
                try {
                    $container = $_name instanceof Tinebase_Model_Container ? $_name : Tinebase_Container::getInstance()->getContainerByName($this->_model, $_name, Tinebase_Model_Container::TYPE_PERSONAL, Tinebase_Core::getUser());
                } catch (Tinebase_Exception_NotFound) {
                    throw new Sabre\DAV\Exception\NotFound('Directory not found');
                }
                if (!Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_READ) || !Tinebase_Core::getUser()->hasGrant($container, Tinebase_Model_Grants::GRANT_SYNC)) {
                    throw new Sabre\DAV\Exception\NotFound('Directory not found');
                }
                
                $objectClass = $this->_applicationName . '_Frontend_WebDAV_Container';
                
                return new $objectClass($container);
        }
        throw new Sabre\DAV\Exception\NotFound('Child not found');
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre\DAV\INode[]
     */
    function getChildren()
    {
        $children = array();
        
        switch (count($this->_pathParts)) {
            # path == ApplicationName
            # return personal and shared folder
            case 1:
                if ($this->_hasPersonalFolders) {
                    $children[] = $this->getChild(Tinebase_Model_Container::TYPE_PERSONAL);
                }
                $children[] = $this->getChild(Tinebase_Model_Container::TYPE_SHARED);
        
                break;
            
            # path == Applicationname/{personal|shared}
            case 2:
                if ($this->_pathParts[1] == Tinebase_Model_Container::TYPE_SHARED) {
                    
                    $containers = Tinebase_Container::getInstance()->getSharedContainer(
                        Tinebase_Core::getUser(),
                        $this->_model,
                        array(
                            Tinebase_Model_Grants::GRANT_READ,
                            Tinebase_Model_Grants::GRANT_SYNC
                        ), false, true
                    );
                    
                    foreach ($containers as $container) {
                        $children[] = $this->getChild($container);
                    }
                } elseif ($this->_hasPersonalFolders && $this->_pathParts[1] == Tinebase_Model_Container::TYPE_PERSONAL) {
                    $children[] = $this->getChild(Tinebase_Core::getUser()->accountLoginName);
                }
                
                break;
                
            # path == Applicationname/personal/accountLoginName
            # return personal folders
            case 3:
                if ($this->_hasPersonalFolders) { 
                    $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_model, Tinebase_Core::getUser(), array(Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_SYNC));
                    foreach ($containers as $container) {
                        $children[] = $this->getChild($container);
                    }
                }
                break;
        }
        
        return $children;
    }
    
    /**
     * return etag
     * 
     * @return string
     */
    public function getETag()
    {
        $etags = array();
        
        foreach ($this->getChildren() as $child) {
            if (method_exists($child, 'getETag')) {
                $etags[] = $child->getETag();
            }
        }
        
        return '"' . sha1(implode('', $etags)) . '"';
    }
    
    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup()
    {
        return null;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\Node::getLastModified()
     */
    public function getLastModified()
    {
        $lastModified = 1;
        
        foreach ($this->getChildren() as $child) {
            $lastModified = $child->getLastModified() > $lastModified ? $child->getLastModified() : $lastModified;
        }
        
        return $lastModified;
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
     * @todo implement real logic
     * @return array
     */
    public function getACL() 
    {
        return null;
        
        return array(
            array(
                        'privilege' => '{DAV:}read',
                        'principal' => $this->addressBookInfo['principaluri'],
                        'protected' => true,
            ),
            array(
                        'privilege' => '{DAV:}write',
                        'principal' => $this->addressBookInfo['principaluri'],
                        'protected' => true,
            )
        );
    
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        [, $name] = Tinebase_WebDav_XMLUtil::splitPath($this->_path);
        
        return $name;
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo implement real logic
     * @return string|null
     */
    public function getOwner()
    {
        return null;
        return $this->addressBookInfo['principaluri'];
    }
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' part count: ' . count($pathParts) . ' ' . print_r($pathParts, true));
        
        $children = array();
        
        [, $basename] = Tinebase_WebDav_XMLUtil::splitPath($this->_path);
        
        switch (count($pathParts)) {
            # path == /accountLoginName
            # list personal and shared folder
            case 1:
                $properties = array(
                    '{http://calendarserver.org/ns/}getctag' => 1,
                    'id'                => $basename,
                    'uri'               => $basename,
                    #'principaluri'      => $principalUri,
                    '{DAV:}displayname' => $basename
                );
                break;
        }
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($requestedProperties, true));
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($properties, true));
        
        $response = array();
    
        foreach ($requestedProperties as $prop) {
            switch($prop) {
                case '{DAV:}owner' :
                    $response[$prop] = new Sabre\DAVACL\Xml\Property\Principal(Sabre\DAVACL\Xml\Property\Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id);
                    break;
                
                case '{DAV:}getetag':
                    $response[$prop] = $this->getETag();
                    break;
                
                default :
                    if (isset($properties[$prop])) $response[$prop] = $properties[$prop];
                    break;
        
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' path: ' . $this->_path . ' ' . print_r($response, true));
        
        return $response;
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl)
    {
        throw new Sabre\DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    protected function _getApplication()
    {
        if ($this->_application == null) {
            $this->_application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        }
        
        return $this->_application;
    }
    
    protected function _parsePath($_path)
    {
        $pathParts = explode('/', trim($this->_path, '/'));
        
        return $pathParts;
    }
    
    /**
     * 
     */
    public function getSupportedPrivilegeSet()
    {
        return null;
    }

    public function propPatch(\Sabre\DAV\PropPatch $propPatch)
    {
        foreach ($this->getChildren() as $child) {
            if ($child instanceof DAV\IProperties) {
                $child->propPatch($propPatch);
            }
        }
    }
}
