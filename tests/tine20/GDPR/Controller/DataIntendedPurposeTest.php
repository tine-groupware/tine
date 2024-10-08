<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     GDPR
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for GDPR_Controller_DataIntendedPurpose
 */
class GDPR_Controller_DataIntendedPurposeTest extends TestCase
{
    /** by default this test runs with an admin user -> should work */
    public function testCreateUpdateSearchDelete()
    {
        $dataIntendedPurpose = new GDPR_Model_DataIntendedPurpose([
            'name' =>                 [[
                GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'test',
            ]],
            'description'   => [[
                GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en', 
                GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'test description'
            ]],
        ]);


        /*** TEST CREATE ***/
        /** @var GDPR_Model_DataIntendedPurpose $createdIntendedPurpose */
        $createdIntendedPurpose = GDPR_Controller_DataIntendedPurpose::getInstance()->create($dataIntendedPurpose);
        static::assertEquals($dataIntendedPurpose['name'][0]['text'], $createdIntendedPurpose['name'][0]['text']);


        /*** TEST GET ***/
        static::assertEquals($createdIntendedPurpose->getId(),
            GDPR_Controller_DataIntendedPurpose::getInstance()->get($createdIntendedPurpose->getId())->getId());


        /*** TEST SEARCH ***/
        static::assertEquals($createdIntendedPurpose->getId(),
            GDPR_Controller_DataIntendedPurpose::getInstance()->search(new GDPR_Model_DataIntendedPurposeFilter([
                ['field' => 'id'      , 'operator' => 'equals', 'value' => $dataIntendedPurpose['id']],
            ]))->getFirstRecord()->getId());


        /*** TEST UPDATE ***/
        $createdIntendedPurpose['name'][0]['text'] = 'testUpated';
        /** @var GDPR_Model_DataProvenance $updatedIntendedPurpose */
        $updatedIntendedPurpose = GDPR_Controller_DataIntendedPurpose::getInstance()->update($createdIntendedPurpose);
        static::assertEquals($createdIntendedPurpose['name'][0]['text'], $updatedIntendedPurpose['name'][0]['text']);

        /*** TEST DELETE ***/
        GDPR_Controller_DataIntendedPurpose::getInstance()->delete($updatedIntendedPurpose);
        try {
            GDPR_Controller_DataIntendedPurpose::getInstance()->get($createdIntendedPurpose->getId());
            static::fail('get after delete did not throw');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        static::assertEquals(0,
            GDPR_Controller_DataIntendedPurpose::getInstance()->search(new GDPR_Model_DataIntendedPurposeFilter([
                ['field' => 'name'      , 'operator' => 'equals', 'value' => $dataIntendedPurpose['name'][0]['text']],
            ]))->count());
        // get deleted record should still work
        static::assertEquals($createdIntendedPurpose->getId(), GDPR_Controller_DataIntendedPurpose::getInstance()
            ->get($createdIntendedPurpose->getId(), null, true, true)->getId());
    }

    public function testAcl()
    {
        $this->_removeRoleRight(GDPR_Config::APP_NAME, Tinebase_Acl_Rights_Abstract::ADMIN);
        // we still have the MANAGE right, so everything should work
        $this->testCreateUpdateSearchDelete();

        $dataIntendedPurpose = new GDPR_Model_DataIntendedPurpose([
            'name' =>                 [[
                GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'en',
                GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'test2',
            ]],
        ], true);

        $createdIntendedPurpose = GDPR_Controller_DataIntendedPurpose::getInstance()->create($dataIntendedPurpose);

        // so, no admin right, no manage right => only get/search should work
        $this->_removeRoleRight(GDPR_Config::APP_NAME, GDPR_Acl_Rights::MANAGE_CORE_DATA_DATA_INTENDED_PURPOSE);

        static::assertEquals($createdIntendedPurpose->getId(),
            GDPR_Controller_DataIntendedPurpose::getInstance()->get($createdIntendedPurpose->getId())->getId());

        static::assertEquals($createdIntendedPurpose->getId(),
            GDPR_Controller_DataIntendedPurpose::getInstance()->search(new GDPR_Model_DataIntendedPurposeFilter([
                ['field' => 'id'      , 'operator' => 'equals', 'value' => $dataIntendedPurpose['id']],
            ]))->getFirstRecord()->getId());

        $dataIntendedPurpose->name =  [
            Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => 'en',
            Tinebase_Record_PropertyLocalization::FLD_TEXT => 'test3',
        ];
        
        try {
            GDPR_Controller_DataIntendedPurpose::getInstance()->create($dataIntendedPurpose);
            static::fail('without admin and manage right, creating should not be possible');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        try {
            GDPR_Controller_DataIntendedPurpose::getInstance()->update($createdIntendedPurpose);
            static::fail('without admin and manage right, updating should not be possible');
        } catch (Tinebase_Exception_AccessDenied $tead) {}

        try {
            GDPR_Controller_DataIntendedPurpose::getInstance()->delete($createdIntendedPurpose);
            static::fail('without admin and manage right, updating should not be possible');
        } catch (Tinebase_Exception_AccessDenied $tead) {}
    }
}
