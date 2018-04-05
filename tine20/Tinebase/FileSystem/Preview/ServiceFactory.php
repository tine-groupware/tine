<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service factory
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Preview_ServiceFactory
{
    /**
     * get Preview Services client
     *
     * @return Tinebase_FileSystem_Preview_ServiceInterface
     * @throws Tinebase_Exception_NotFound PreviewService version not found
     */
    public static function getPreviewService()
    {
        $version = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERSION};
        switch ($version) {
            case 1:
                return new Tinebase_FileSystem_Preview_ServiceV1();
            case 2:
                $licenseClass = Tinebase_License::getInstance();

                $licensePath = 'tine20://' . $licenseClass->getLicensePath();
                $caPath = dirname($licenseClass->getCaFiles()[0]);
                $url = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL};

                $networkAdapter = new Tinebase_FileSystem_Preview_AuthNetworkAdapter($url, $licensePath, $caPath);
                return new Tinebase_FileSystem_Preview_ServiceV2($networkAdapter);
        }
        throw new Tinebase_Exception_NotFound("Preview Service Version not found", 500);
    }
}
