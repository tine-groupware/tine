<?php

/**
 * JSON interface for bookmarks
 *
 * @package    Bookmarks
 * @subpackage Frontend
 */
class Bookmarks_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'Bookmarks';
    protected $_configuredModels = [
        Bookmarks_Model_Bookmark::MODEL_NAME_PART
    ];

    /**
     * Returns registry data
     * @return array
     */
    public function getRegistryData(): array
    {
        $containers = $this->getDefaultBookmarksContainers();
        $registryData = array(
            'defaultBookmarksContainer' => reset($containers)
        );
        return array_merge($registryData, $this->_getImportDefinitionRegistryData());
    }

    /**
     * get default bookmarks container
     *
     * @return array
     */
    public function getDefaultBookmarksContainers(): array
    {
        $user = Tinebase_Core::getUser();
        try {
            $defaultBookmarksContainers = Tinebase_Container::getInstance()->getPersonalContainer($user, Bookmarks_Model_Bookmark::class, $user, Tinebase_Model_Grants::GRANT_ADMIN);
        } catch (Tinebase_Exception_NotFound $e) {
            return [];
        }
        return $defaultBookmarksContainers->toArray();
    }
}
