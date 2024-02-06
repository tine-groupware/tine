<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

interface Tinebase_Model_Tree_FlySystem_AdapterConfig_Interface
{
    public function getFlySystemAdapter(): \League\Flysystem\FilesystemAdapter;
}