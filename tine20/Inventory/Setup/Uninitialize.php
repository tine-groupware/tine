<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Inventory initialization
 *
 * @package     Inventory
 */
class Inventory_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializeCostCenter()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }
        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [Inventory_Model_InventoryItem::class]);
    }
}