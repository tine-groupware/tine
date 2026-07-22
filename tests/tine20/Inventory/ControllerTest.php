<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Inventory
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Inventory_ControllerTest
 */
class Inventory_ControllerTest extends Inventory_TestCase
{
    public function testAttachmentCreation(): void
    {
        $this->markTestSkipped('template file needs to be fixed, ci doesnt do pdf conversion?');
        
        $this->_testNeedsTransaction();

        $orgFsConfig = $fsConfig = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->toArray();
        $raii = new Tinebase_RAII(function() use($orgFsConfig) {
            Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::FILESYSTEM, $orgFsConfig);
            $db = Tinebase_Core::getDb();
            $db->query('TRUNCATE ' . SQL_TABLE_PREFIX . OnlyOfficeIntegrator_Model_AccessToken::TABLE_NAME);
            $db->query('TRUNCATE ' . SQL_TABLE_PREFIX . Inventory_Model_InventoryItem::TABLE_NAME);
            $db->query('TRUNCATE ' . SQL_TABLE_PREFIX . Inventory_Model_ElectricalEquipment::TABLE_NAME);
            $db->query('TRUNCATE ' . SQL_TABLE_PREFIX . Inventory_Model_ElectricalSafetyTest::TABLE_NAME);
        });
        $fsConfig[Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERSION] = -1;
        Tinebase_Config::getInstance()->setInMemory(Tinebase_Config::FILESYSTEM, $fsConfig);

        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'testAttachmentData');

        $item = Inventory_Controller_InventoryItem::getInstance()->create(new Inventory_Model_InventoryItem([
            Inventory_Model_InventoryItem::FLD_NAME => 'a',
            'attachments' => new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [[
                    'name'      => 'testAttachmentData.txt',
                    'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path)
                ]], true),
            Inventory_Model_InventoryItem::FLD_ELECTRICAL_EQUIPMENTS => new Tinebase_Record_RecordSet(Inventory_Model_ElectricalEquipment::class, [
                new Inventory_Model_ElectricalEquipment([
                    Inventory_Model_ElectricalEquipment::FLD_NAME => 'a',
                    Inventory_Model_ElectricalEquipment::FLD_INVENTORY_ID => 'inventory id unittest',
                    Inventory_Model_ElectricalEquipment::FLD_PROTECTION_CLASS => 'I',
                    Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS => new Tinebase_Record_RecordSet(Inventory_Model_ElectricalSafetyTest::class, [
                        new Inventory_Model_ElectricalSafetyTest([
                            Inventory_Model_ElectricalSafetyTest::FLD_VISUAL_INSPECTION_PASSED => false,
                            Inventory_Model_ElectricalSafetyTest::FLD_PROTECTIVE_CONDUCTOR_RESISTANCE => 0.5,
                            Inventory_Model_ElectricalSafetyTest::FLD_INSULATION_RESISTANCE => 0.6,
                            Inventory_Model_ElectricalSafetyTest::FLD_PROTECTIVE_CONDUCTOR_CURRENT => 0.7,
                            Inventory_Model_ElectricalSafetyTest::FLD_TOUCH_CURRENT => 0.8,
                            Inventory_Model_ElectricalSafetyTest::FLD_TEST_PASSED => false,
                        ], true)
                    ])
                ], true),
            ])
        ], true));

        $equipment = $item->{Inventory_Model_InventoryItem::FLD_ELECTRICAL_EQUIPMENTS}->getFirstRecord();
        $this->assertCount(2, $item->attachments);
        $this->assertSame(Tinebase_DateTime::today()->format('Y-m-d'), $equipment->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE}->format('Y-m-d'));
        foreach ($item->attachments as $attachment) {
            if ($attachment->name === 'testAttachmentData.txt') {
                continue;
            }
            $data = file_get_contents('tine20:///Inventory/folders' . $attachment->path);
            $pdf = (new \Smalot\PdfParser\Parser())->parseContent($data);
            $text = $pdf->getText();
            //$this->assertStringContainsString('inventory id unittest', $text);
        }

        $equipment->{Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS}->addRecord(new Inventory_Model_ElectricalSafetyTest([
            Inventory_Model_ElectricalSafetyTest::FLD_VISUAL_INSPECTION_PASSED => true,
            Inventory_Model_ElectricalSafetyTest::FLD_PROTECTIVE_CONDUCTOR_RESISTANCE => 0.5,
            Inventory_Model_ElectricalSafetyTest::FLD_INSULATION_RESISTANCE => 0.6,
            Inventory_Model_ElectricalSafetyTest::FLD_PROTECTIVE_CONDUCTOR_CURRENT => 0.7,
            Inventory_Model_ElectricalSafetyTest::FLD_TOUCH_CURRENT => 0.8,
            Inventory_Model_ElectricalSafetyTest::FLD_TEST_PASSED => true,
            Inventory_Model_ElectricalSafetyTest::FLD_TEST_DATE => $testDate = Tinebase_DateTime::today()->addDay(5),
        ], true));
        $item = Inventory_Controller_InventoryItem::getInstance()->update($item);
        $equipment = $item->{Inventory_Model_InventoryItem::FLD_ELECTRICAL_EQUIPMENTS}->getFirstRecord();

        $this->assertCount(3, $item->attachments);
        $this->assertCount(2, $equipment->{Inventory_Model_ElectricalEquipment::FLD_ELECTRICAL_SAFETY_TESTS});
        $this->assertSame($testDate->add(new DateInterval(Inventory_Config::getInstance()->{Inventory_Config::ELECTRICAL_SAFETY_TEST_INTERVAL}))->format('Y-m-d'),
            $equipment->{Inventory_Model_ElectricalEquipment::FLD_NEXT_TEST_DUE}->format('Y-m-d'));
    }
}
