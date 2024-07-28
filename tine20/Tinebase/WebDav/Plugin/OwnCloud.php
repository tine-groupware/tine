<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2013-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ownCloud Integrator plugin
 *
 * This plugin provides functionality reuqired by ownCloud sync clients
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Tinebase_WebDav_Plugin_OwnCloud extends Sabre\DAV\ServerPlugin
{

    const NS_OWNCLOUD = 'http://owncloud.org/ns';

    /**
     * Min version of owncloud
     */
    const OWNCLOUD_MIN_VERSION = '2.0.0';

    /**
     * Max version of owncloud
     *
     * Adjust max version of supported owncloud clients for tine
     */
    const OWNCLOUD_MAX_VERSION = '100.0.0';

    private $_clientVersion = false;
    private $_clientPlatform = 'mirall';

    /**
     * Reference to server object
     *
     * @var Sabre\DAV\Server
     */
    private $server;

    /**
     * Initializes the plugin
     *
     * @param Sabre\DAV\Server $server
     * @return void
     */
    public function initialize(Sabre\DAV\Server $server)
    {
        $this->server = $server;

        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));

        $this->getOwnCloudVersion(); // get/set client version and platform

        /* Namespaces */
        $server->xmlNamespaces[self::NS_OWNCLOUD] = ($this->_clientPlatform == 'iOS') ? 'oc' : 'owncloud';

        array_push($server->protectedProperties,
            '{' . self::NS_OWNCLOUD . '}id'
        );
        array_push($server->protectedProperties,
            '{' . self::NS_OWNCLOUD . '}permissions'
        );
        array_push($server->protectedProperties,
            '{' . self::NS_OWNCLOUD . '}privatelink'
        );
    }

    /**
     * Adds ownCloud specific properties
     *
     * @param string $path
     * @param \Sabre\DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     * @throws \InvalidArgumentException
     */
    public function beforeGetProperties(
        $path,
        Sabre\DAV\INode $node,
        array &$requestedProperties,
        array &$returnedProperties
    ) {
        if ($this->_clientVersion !== null && !$this->isValidOwnCloudVersion()) {
            $message = sprintf(
                '%s::%s OwnCloud client min version is "%s"!',
                __METHOD__,
                __LINE__,
                static::OWNCLOUD_MIN_VERSION
            );

            Tinebase_Core::getLogger()->debug($message);
            throw new InvalidArgumentException($message);
        } elseif (!$this->_clientVersion) {
            // If it's not even an owncloud version, don't add any owncloud specific features here.
            return;
        }

        $id = '{' . self::NS_OWNCLOUD . '}id';

        if (in_array($id, $requestedProperties)) {
            unset($requestedProperties[array_search($id, $requestedProperties)]);
            if ($node instanceof Tinebase_Frontend_WebDAV_Node) {
                $returnedProperties[200][$id] = $node->getId();
            } else {
                // the path does not change for the other nodes => hence the id is "static"
                $returnedProperties[200][$id] = sha1($path);
            }
        }

        $permission = '{' . self::NS_OWNCLOUD . '}permissions';
        if (in_array($permission, $requestedProperties)) {
            unset($requestedProperties[array_search($permission, $requestedProperties)]);
            $returnedProperties[200][$permission] = 'S';
            if ($node instanceof Tinebase_Frontend_WebDAV_Node && ($fNode = $node->getNode())) {
                $grants = Tinebase_FileSystem::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $fNode);
                if ($grants->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                    $returnedProperties[200][$permission] .= 'WCKDR';
                } else {
                    if ($grants->{Tinebase_Model_Grants::GRANT_DELETE}) {
                        $returnedProperties[200][$permission] .= 'D';
                    }
                    if ($grants->{Tinebase_Model_Grants::GRANT_EDIT}) {
                        $returnedProperties[200][$permission] .= 'W';
                    }
                    if ($grants->{Tinebase_Model_Grants::GRANT_ADD}) {
                        $returnedProperties[200][$permission] .= 'CK';
                    }
                    if ($grants->{Tinebase_Model_Grants::GRANT_PUBLISH}) {
                        $returnedProperties[200][$permission] .= 'R';
                    }
                }
            }
        }

        $fingerPrint = '{' . self::NS_OWNCLOUD . '}data-fingerprint';
        if (in_array($fingerPrint, $requestedProperties)) {
            unset($requestedProperties[array_search($fingerPrint, $requestedProperties)]);
            $returnedProperties[200][$fingerPrint] = '';
        }

        $shareTypes = '{' . self::NS_OWNCLOUD . '}share-types';
        if (in_array($shareTypes, $requestedProperties)) {
            unset($requestedProperties[array_search($shareTypes, $requestedProperties)]);
            $returnedProperties[200][$shareTypes] = '';
        }
        
        $privateLink = '{' . self::NS_OWNCLOUD . '}privatelink';
        if (in_array($privateLink, $requestedProperties)) {
            if ($node instanceof Tinebase_Frontend_WebDAV_Node || $node instanceof Filemanager_Frontend_WebDAV) {
                unset($requestedProperties[array_search($shareTypes, $requestedProperties)]);
                $paths = $node->getPath();
                $splitPath = explode('/', trim($paths, '/'));
                $paths = array_slice($splitPath, 2);
                if ($paths[0] === Tinebase_Model_Container::TYPE_PERSONAL) {
                    $account = Tinebase_User::getInstance()->getUserById($paths[1]);
                    $paths[1] = $account->accountLoginName;
                }
                $path = join('/', $paths);
                $returnedProperties[200][$privateLink] = Tinebase_Core::getUrl() . '/#/Filemanager/' . $path;
            }
        }

    }

    /**
     * Return the actuall owncloud version number
     * @throws \InvalidArgumentException
     */
    protected function isValidOwnCloudVersion()
    {
        return version_compare($this->_clientVersion, static::OWNCLOUD_MIN_VERSION, 'ge')
            && version_compare($this->_clientVersion, static::OWNCLOUD_MAX_VERSION, 'le');
    }

    /**
     * Get owncloud version number
     *
     * @return mixed|null
     */
    protected function getOwnCloudVersion() {
        // Windows: Mozilla/5.0 (Macintosh) mirall/2.2.4 (build 3709)
        // Android: Mozilla/5.0 (Android) ownCloud-android/3.0.4 
        // iOs: ownCloudApp/12.2.1 (App/291; iOS/17.5.1; iPhone)
        /* @var $request \Zend\Http\PhpEnvironment\Request */
        $request = Tinebase_Core::get(Tinebase_Core::REQUEST);

        // In some cases this is called not out of an request, for example some tests, therefore we should require it here
        // If it's not an owncloud server, we don't need to determine the version!
        if (!$request) {
            return null;
        }

        $useragentHeader = $request->getHeader('user-agent');

        $useragent = $useragentHeader ? $useragentHeader->getFieldValue() : null;

        // If no valid header, this is not an owncloud client
        if ($useragent === null) {
            return null;
        }

        $match = [];

        if (!preg_match('/(mirall|ownCloud-android|ownCloudApp)\/(\d+\.\d+\.\d+)/', $useragent, $match)) {
            return null;
        }

        $version = array_pop($match);

        if ($version === '') {
            $version = null;
        }

        $this->_clientVersion = $version;

        $platform = 'mirall';
        if ($match[1] == 'ownCloud-android') {
            $platform = 'Android';
        }
        else if (($match[1] == 'ownCloudApp') && ((strpos($useragent, 'iPad') > 0) || (strpos($useragent, 'iPhone') > 0))) {
             $platform = 'iOS';
        }
        $this->_clientPlatform = $platform;

        return $version;
    }
}
