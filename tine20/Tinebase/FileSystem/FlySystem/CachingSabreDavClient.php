<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use League\Flysystem\WebDAV\WebDAVAdapter;
use League\Flysystem\WhitespacePathNormalizer;
use Sabre\DAV\Client;

class Tinebase_FileSystem_FlySystem_CachingSabreDavClient extends Client
{
    protected array $propFindCache = [];

    public function propFind($url, array $properties, $depth = 0)
    {
        if (empty(array_diff($properties, WebDAVAdapter::FIND_PROPERTIES))) {
            if ($this->propFindCache[$depth][$url] ?? false) {
                return $this->propFindCache[$depth][$url];
            }
            $requestProperties = WebDAVAdapter::FIND_PROPERTIES;
        } else {
            $requestProperties = array_unique(array_merge($properties, WebDAVAdapter::FIND_PROPERTIES));
        }

        $result = parent::propFind($url, $requestProperties, $depth);
        $this->propFindCache[$depth][$url] = $result;
        if (1 === $depth) {
            array_shift($result);
            foreach ($result as $path => $data) {
                $path = (new WhitespacePathNormalizer())->normalizePath($path);
                $this->propFindCache[0][$path] = $data;
            }
        }
        return $this->propFindCache[$depth][$url];
    }
}