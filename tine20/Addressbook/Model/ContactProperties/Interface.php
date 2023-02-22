<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

interface Addressbook_Model_ContactProperties_Interface
{
    static public function updateCustomFieldConfig(Tinebase_Model_CustomField_Config $cfc,
                                                   Addressbook_Model_ContactProperties_Definition $def): void;
}