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

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;

/**
 * ownCloud Integrator plugin
 *
 * This plugin provides functionality reuqired by ownCloud sync clients
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Tinebase_WebDav_Plugin_OwnCloud extends \Sabre\DAV\ServerPlugin
{

    public const NS_OWNCLOUD = 'http://owncloud.org/ns';

    /**
     * Min version of owncloud
     */
    public const OWNCLOUD_MIN_VERSION = '2.0.0';

    /**
     * Max version of owncloud
     *
     * Adjust max version of supported owncloud clients for tine
     */
    public const OWNCLOUD_MAX_VERSION = '100.0.0';

    /**
     * Client apps
     */
    public const USER_AGENTS = [
        'mirall',                   // Windows: Mozilla/5.0 (Macintosh) mirall/2.2.4 (build 3709)
        'ownCloud-android',         // Android: Mozilla/5.0 (Android) ownCloud-android/3.0.4
        'Owncloud\siOs\sClient',    // iOs: <old app>
        'ownCloudApp',              // iOS/iPad new: ownCloudApp/12.2.1 (App/291; iOS/17.5.1; iPhone)
    ];

    protected string $useragent;

    protected \Sabre\DAV\Server $server;

    /**
     * Initializes the plugin
     *
     * @param \Sabre\DAV\Server $server
     * @return void
     */
    public function initialize(\Sabre\DAV\Server $server)
    {
        $this->server = $server;

        $this->useragent = $server->httpRequest->getHeader('user-agent') ?? '';

        $server->on('propFind', $this->propFind(...));

        /* Namespaces | new iOS ownCloudApp uses 'oc' hardcoded */
        $server->xml->namespaceMap[self::NS_OWNCLOUD] = ($this->getOwnCloudClientPlatform() == 'iOS') ? 'oc' : 'owncloud';
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

    public function propFind(PropFind $propFind, INode $node)
    {
        $version = $this->getOwnCloudVersion();
        if ($version !== null && !$this->isValidOwnCloudVersion($version)) {
            $message = sprintf(
                '%s::%s OwnCloud client min version is "%s"!',
                __METHOD__,
                __LINE__,
                static::OWNCLOUD_MIN_VERSION
            );
            Tinebase_Core::getLogger()->debug($message);
            throw new InvalidArgumentException($message);
        } elseif (!$version) {
            // If it's not even an owncloud version, don't add any owncloud specific features here.
            return;
        }

        $propFind->handle('{' . self::NS_OWNCLOUD . '}id',
            $node instanceof Tinebase_Frontend_WebDAV_Node ? $node->getId() :
                // the path does not change for the other nodes => hence the id is "static"
                sha1($propFind->getPath()));

        // new iPhone/iPad ownCloudApp relays on OC-CHECKSUM header hardcoded (without: download-loop!!)
        // Even if '{' . self::NS_OWNCLOUD . '}checksums' is not requested...
        if (($node instanceof Tinebase_Frontend_WebDAV_Node) && ($fNode = $node->getNode())) {
            $this->server->httpResponse->setHeader('OC-CHECKSUM', 'sha1:' . $fNode->hash);
        }

        $propFind->handle('{' . self::NS_OWNCLOUD . '}permissions', function() use($node) {
            $permission = 'S';
            $fNode = null;
            if ( $node instanceof Tinebase_Frontend_WebDAV_Node) {
                $fNode = $node->getNode();
            }
            if ($node instanceof Filemanager_Frontend_WebDAV) {
                try {
                    $path = $node->getPath();
                    $fNode = Tinebase_FileSystem::getInstance()->stat($path);
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ .
                        '::' . __LINE__ . ' Could not get node from instance Filemanager_Frontend_WebDAV : ' . $e->getMessage());                }
            }
            if ($fNode) {
                $grants = Tinebase_FileSystem::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $fNode);
                if ($grants->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                    $permission .= 'WCKDR';
                } else {
                    if ($grants->{Tinebase_Model_Grants::GRANT_DELETE}) {
                        $permission .= 'D';
                    }
                    if ($grants->{Tinebase_Model_Grants::GRANT_EDIT}) {
                        $permission .= 'W';
                    }
                    if ($grants->{Tinebase_Model_Grants::GRANT_ADD}) {
                        $permission .= 'CK';
                    }
                    if ($grants->{Tinebase_Model_Grants::GRANT_PUBLISH}) {
                        $permission .= 'R';
                    }
                }
            }
            return $permission;
        });

        $propFind->handle('{' . self::NS_OWNCLOUD . '}data-fingerprint', '');
        $propFind->handle('{' . self::NS_OWNCLOUD . '}share-types', '');


        if ($node instanceof Tinebase_Frontend_WebDAV_Node || $node instanceof Filemanager_Frontend_WebDAV) {
            $propFind->handle('{' . self::NS_OWNCLOUD . '}privatelink', function() use ($node) {
                $paths = $node->getPath();
                $splitPath = explode('/', trim($paths, '/'));
                $paths = array_slice($splitPath, 2);
                if ($paths[0] === Tinebase_Model_Container::TYPE_PERSONAL) {
                    $account = Tinebase_User::getInstance()->getUserById($paths[1]);
                    $paths[1] = $account->accountLoginName;
                }
                $path = join('/', $paths);
                return Tinebase_Core::getUrl() . '/#/Filemanager/' . $path;
            });
        }
    }

    /**
     * Return the actuall owncloud version number
     * @throws \InvalidArgumentException
     */
    protected function isValidOwnCloudVersion($version)
    {
        return version_compare($version, static::OWNCLOUD_MIN_VERSION, 'ge')
            && version_compare($version, static::OWNCLOUD_MAX_VERSION, 'le');
    }

    /**
     * Get owncloud client platform
     */
    protected function getOwnCloudClientPlatform(): ?string
    {
        $match = [];
        if (!preg_match('/('. implode('|', static::USER_AGENTS) .')\/(\d+\.\d+\.\d+)/', $this->useragent, $match)) {
            return null;
        }

        $platform = 'mirall'; // Desktop app is default
        if ($match[1] == 'ownCloud-android') {
            $platform = 'Android';
        }
        else if (($match[1] == 'ownCloudApp') && ((strpos($this->useragent, 'iPad') > 0) || (strpos($this->useragent, 'iPhone') > 0))) {
            $platform = 'iOS';
        }

        return $platform;
    }

    /**
     * Get owncloud version number
     *
     * @return mixed|null
     */
    protected function getOwnCloudVersion()
    {
        // If no valid header, this is not an owncloud client
        if ($this->useragent === null) {
            return null;
        }

        $match = [];

        if (!preg_match('/('. implode('|', static::USER_AGENTS) .')\/(\d+\.\d+\.\d+)/', $this->useragent, $match)) {
            return null;
        }

        $version = array_pop($match);

        if ($version === '') {
            $version = null;
        }

        return $version;
    }
}
