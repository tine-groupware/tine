<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 *
 */

/**
 * class to handle webdav requests for Tinebase
 *
 * @package     Tinebase
 *
 * @todo extend Tinebase_Frontend_WebDAV_Record? or maybe add a common ancestor
 */
abstract class Tinebase_Frontend_WebDAV_Node implements Sabre\DAV\INode, \Sabre\DAV\IProperties,
    Tinebase_Frontend_WebDAV_IRenamable
{
    /**
     * @var Tinebase_Model_Tree_Node
     */
    protected $_node;

    protected $_container;

    /**
     * @var array list of forbidden file names
     */
    protected static $_forbiddenNames = array('.DS_Store', 'Thumbs.db');

    protected static $_CONTENT_LENGTH;

    public function __construct(protected $_path)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $this->_path);

        try {
            $this->_node = Tinebase_FileSystem::getInstance()->stat($this->_path);
        } catch (Tinebase_Exception_NotFound) {}

        if (! $this->_node) {
            throw new Sabre\DAV\Exception\NotFound('Filesystem path: ' . $this->_path . ' not found');
        }

        // You cannot rely on HTTP_* in some cases, see https://www.php.net/reserved.variables.server comment #15
        self::$_CONTENT_LENGTH = $_SERVER['HTTP_CONTENT_LENGTH'] ?? $_SERVER['CONTENT_LENGTH'] ?? false;
    }

    public function getId()
    {
        return $this->_node->getId();
    }

    public function getName()
    {
        [, $basename] = Tinebase_WebDav_XMLUtil::splitPath($this->_path);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . $basename);

        return $basename;
    }

    /**
     * Returns the last modification time
     *
     * @return int
     */
    public function getLastModified()
    {
        if ($this->_node instanceof Tinebase_Model_Tree_Node) {
            if ($this->_node->last_modified_time instanceof Tinebase_DateTime) {
                return $this->_node->last_modified_time->getTimestamp();
            } else if ($this->_node->creation_time instanceof Tinebase_DateTime) {
                return $this->_node->creation_time->getTimestamp();
            }
        }

        return Tinebase_DateTime::now()->getTimestamp();
    }

    /**
     * Renames the node
     *
     * @throws Sabre\DAV\Exception\Forbidden
     * @throws Sabre\DAV\Exception\NotFound
     * @param string $name The new name
     * @return void
     */
    public function setName($name)
    {
        list($dirname,) = Tinebase_WebDav_XMLUtil::splitPath($this->_path);
        $this->rename($dirname . '/' . $name);
    }

    public function rename(string $newPath)
    {
        list($dirname, $newName) = Tinebase_WebDav_XMLUtil::splitPath($newPath);
        self::checkForbiddenFile($newName);

        if (!Tinebase_FileSystem::getInstance()->checkPathACL(Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path),
                'delete', true, false)) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to move file: ' . $this->_path);
        }

        if (!Tinebase_FileSystem::getInstance()->checkPathACL(Tinebase_Model_Tree_Node_Path::createFromStatPath($dirname),
                'add', true, false)) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to create file: ' . $newPath);
        }

        try {
            $result = Tinebase_FileSystem::getInstance()->rename($this->_path, $newPath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\NotFound($tenf->getMessage());
        }
        if (! $result) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        $this->_node = $result;
        $this->_path = $result->path;
    }

    /**
     * return container for given path
     *
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getContainer()
    {
        if (null === $this->_container) {
            $this->_container = Tinebase_FileSystem::getInstance()->get($this->_node->parent_id);
        }

        return $this->_container;
    }

   /**
    * checks if filename is acceptable
    *
    * @param  string $name
    * @throws Sabre\DAV\Exception\Forbidden
    */
    public static function checkForbiddenFile($name)
    {
        if (in_array($name, self::$_forbiddenNames)) {
            throw new Sabre\DAV\Exception\Forbidden('forbidden name' . ' : ' . $name);
        } else if (str_starts_with($name, '._')) {
            throw new Sabre\DAV\Exception\Forbidden('no resource files accepted' . ' : ' . $name);
        } else if (preg_match(Tinebase_Model_Tree_Node::FILENAME_FORBIDDEN_CHARS_EXP, $name, $matches)) {
            throw new Sabre\DAV\Exception\Forbidden('Illegal characters: ' . $name . ' => ' . print_r($matches, true));
        }
    }

    /**
     * return etag
     *
     * @return string
     */
    public function getETag()
    {
        return '"' . (empty($this->_node->hash) ? sha1($this->_node->object_id) : $this->_node->hash) . '"';
    }

    /**
     * Returns the content sequence for this container
     *
     * @return string
     */
    public function getSyncToken()
    {
        // this only returns null if the container is not found or if container.content_seq = NULL, this does not look up the content history!
        return $this->_node->seq;
    }

    /**
     * returns the nodes size
     *
     * @return integer
     */
    public function getSize()
    {
        return (int)$this->_node->size;
    }

    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $response = array();

        foreach ($requestedProperties as $prop) {
            switch($prop) {
                case '{DAV:}getcontentlength':
                    if ($this->_node->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                        $response[$prop] = $this->getSize();
                    }
                    break;

                case '{http://owncloud.org/ns}size':
                    $response[$prop] = $this->getSize();
                    break;

                case '{DAV:}getetag':
                    $response[$prop] = $this->getETag();
                    break;

                case '{DAV:}sync-token':
                    if (Tinebase_Config::getInstance()->get(Tinebase_Config::WEBDAV_SYNCTOKEN_ENABLED)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport enabled');
                        $response[$prop] = $this->getSyncToken();
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport disabled');
                    }
                    break;
                case '{DAV:}quota-available-bytes':
                    $quotaData = Tinebase_FileSystem::getInstance()->getEffectiveAndLocalQuota($this->_node);
                    // 200 GB limit in case no quota provided
                    $response[$prop] = $quotaData['localQuota'] === null ? 200 * 1024 * 1024 * 1024 :
                        $quotaData['localFree'];

                    break;

                case '{DAV:}quota-used-bytes':
                    if (Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}
                        ->{Tinebase_Config::QUOTA_INCLUDE_REVISION}) {
                        $size = $this->_node->size;
                    } else {
                        $size = $this->_node->revision_size;
                    }
                    $response[$prop] = $size;
                    break;
            }
        }

        return $response;
    }

    public function propPatch(\Sabre\DAV\PropPatch $propPatch)
    {
        // no write access, we don't do anything, the propPatch will fail with 403 automatically
    }

    public function getNode()
    {
        return $this->_node;
    }

    public function getPath()
    {
        return $this->_path;
    }
}
