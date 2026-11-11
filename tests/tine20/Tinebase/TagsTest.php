<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Tags
 */
class Tinebase_TagsTest extends TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Tags
     */
    protected $_instance;

    /**
     * (non-PHPdoc)
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    public function setUp(): void
{
        parent::setUp();
        
        $this->_instance = Tinebase_Tags::getInstance();
    }

    /**
     * create tags
     */
    public function testCreateTags()
    {
        $this->_createSharedTag();

        $personalTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'  => 'tag::personal',
            'description' => 'this is a personal tag of account 1',
            'color' => '#FF0000',
        ));
        $savedPersonalTag = $this->_instance->createTag($personalTag);
        $this->assertEquals($personalTag->description, $savedPersonalTag->description);
    }

    /**
     * test resolving tag names to Tinebase_Model_Tag
     */
    public function testResolveTagNames()
    {
        $sharedTag = $this->_createSharedTag();
        
        $resolvedTags = Tinebase_Model_Tag::resolveTagNameToTag($sharedTag->name, 'Addressbook');
        
        $this->assertContains($sharedTag->name, $resolvedTags->name);
    }
    
    /**
     * test search tags
     */
    public function testSearchTags()
    {
        $sharedTag = $this->_createSharedTag();

        $filter = new Tinebase_Model_TagFilter(array(
            'name' => 'tagSingle::%'
        ));
        $paging = new Tinebase_Model_Pagination();
        $tags = $this->_instance->searchTags($filter, $paging);
        $count = $this->_instance->getSearchTagsCount($filter);

        $this->assertTrue($count > 0, 'did not find created tag');
        $this->assertStringContainsString('tagSingle::', $tags->getFirstRecord()->name);
    }

    /**
     * attach tags to records
     */
    public function testAttachTagToMultipleRecords()
    {
        $personas = Zend_Registry::get('personas');
        $personasContactIds = array();
        foreach ($personas as $persona) {
            $personasContactIds[] = $persona->contact_id;
        }

        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);
        foreach ($contacts as $contact) {
            $contact->tags = array();
            $this->_instance->setTagsOfRecord($contact);
        }

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $personasContactIds)
        ));

        $tagData = array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'tag::testAttachTagToMultipleRecords',
            'description' => 'testAttachTagToMultipleRecords',
            'color' => '#009B31',
        );

        $this->_instance->attachTagToMultipleRecords($filter, $tagData);

        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);

        $this->_instance->getMultipleTagsOfRecords($contacts);
        foreach ($contacts as $contact) {
            $this->assertEquals(1, count($contact->tags), 'Tag not found in contact ' . $contact->n_fn);
        }
    }

    public function testAttachSystemTagToRecord()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'org_name' => Tinebase_Record_Abstract::generateUID(10)
        ]));

        $tag = $this->_createSharedTag(['system_tag' => true]);
        $contact->tags = [$tag];

        $this->_instance->attachSystemTag($contact, $tag);

        self::assertEquals(1, count($contact->tags), 'Tag not found in contact ' . $contact->n_fn);

        $this->_instance->detachSystemTag($contact, $tag);
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact->getId());
        self::assertEquals(0, count($contact->tags), 'Tag should not be found in contact '
            . $contact->n_fn);

        Addressbook_Controller_Contact::getInstance()->delete([$contact->getId()]);
    }

    /**
    * detach tags from records
    */
    public function testDetachTagsFromMultipleRecords()
    {
        $personas = Zend_Registry::get('personas');
        $personasContactIds = array();
        foreach ($personas as $persona) {
            $personasContactIds[] = $persona->contact_id;
        }

        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);
        foreach ($contacts as $contact) {
            $contact->tags = array();
            $this->_instance->setTagsOfRecord($contact);
        }

        $filter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => $personasContactIds)
        ));

        $tagData1 = array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'tagMulti::test1',
            'description' => 'testDetachTagToMultipleRecords1',
            'color' => '#009B31',
        );
        $tag1 = $this->_instance->attachTagToMultipleRecords($filter, $tagData1);
        $tagIds[] = $tag1->getId();

        $tagData2 = array(
            'type'  => Tinebase_Model_Tag::TYPE_PERSONAL,
            'name'  => 'tagMulti::test2',
            'description' => 'testDetachTagToMultipleRecords2',
            'color' => '#ff9B31',
        );
        $tag2 = $this->_instance->attachTagToMultipleRecords($filter, $tagData2);
        $tagIds[] = $tag2->getId();
        
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);

        $this->_instance->getMultipleTagsOfRecords($contacts);
        foreach ($contacts as $contact) {
            $this->assertEquals(2, count($contact->tags), 'Tags not found in contact ' . $contact->n_fn);
        }
        
        // Try to remove the created Tags
        try {
            $this->_instance->detachTagsFromMultipleRecords($filter, $tagIds);
        } catch (Zend_Db_Statement_Exception $zdse) {
            $this->fail('failed to detach tags: ' . print_r($tagIds, TRUE) . ' / exception: ' . $zdse);
        }

        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);

        $this->_instance->getMultipleTagsOfRecords($contacts);
        foreach ($contacts as $contact) {
            $this->assertEquals(0, count($contact->tags), 'Tags should not be found not found in contact ' . $contact->n_fn);
        }
    }

    /**
    * test search tags with 'attached' filter
    */
    public function testSearchTagsByForeignFilter()
    {
        $sharedTag = $this->_createSharedTag();
        $filter = new Addressbook_Model_ContactFilter();
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $sharedTag);

        $tags = $this->_instance->searchTagsByForeignFilter($filter);
        $this->assertTrue(count($tags) > 0);
        $sharedTagInResult = NULL;
        foreach ($tags as $tag) {
            if ($tag->getId() === $sharedTag->getId()) {
                $sharedTagInResult = $tag;
                break;
            }
        }
        $this->assertTrue($sharedTagInResult instanceof Tinebase_Model_Tag, 'shared tag not found');
        $this->assertEquals(Addressbook_Controller_Contact::getInstance()->searchCount($filter), $sharedTagInResult->selection_occurrence);
    }
    
    /**
     * testMergeDuplicateTags
     * 
     * @see 0007354: function for merging duplicate tags
     */
    public function testMergeDuplicateTags()
    {
        $sharedTag1 = $this->_createSharedTag();
        // sleep to make sure, $sharedTag1 is always chosen as 'master'
        sleep(1);
        $sharedTag2 = $this->_createSharedTag();
        
        $contactIds = Addressbook_Controller_Contact::getInstance()->search(
            new Addressbook_Model_ContactFilter(),
            new Tinebase_Model_Pagination(array(
                'limit' => 6
            )))->getArrayOfIds();

        // attach to first 3 contacts
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array_slice($contactIds, 0, 3))
        ));
        $sharedTag1 = Tinebase_Tags::getInstance()->attachTagToMultipleRecords($contactFilter, $sharedTag1);
        self::assertEquals(3, $sharedTag1->occurrence);

        // attach to next 3 contacts
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array_slice($contactIds, 3, 3))
        ));
        $sharedTag2 = Tinebase_Tags::getInstance()->attachTagToMultipleRecords($contactFilter, $sharedTag2);
        
        $this->_instance->mergeDuplicateSharedTags('Addressbook_Model_Contact');
        
        $sharedTag1AfterMerge = $this->_instance->get($sharedTag1);
        self::assertEquals($sharedTag1->occurrence + 3, $sharedTag1AfterMerge->occurrence,
            'occurrence should have been increased by three: ' . print_r($sharedTag1AfterMerge->toArray(), TRUE));
        $this->expectException('Tinebase_Exception_NotFound');
        $this->_instance->get($sharedTag2);
    }
    
    /**
     * test search tags count
     * 
     * @see 0008170: wrong paging in admin menu for TAGS
     */
    public function testSearchTagsCount()
    {
        $filter = new Tinebase_Model_TagFilter(array('type' => Tinebase_Model_Tag::TYPE_SHARED));
        $count = $this->_instance->getSearchTagsCount($filter);
        
        if ($count < 50) {
            // create up to 50 tags
            for ($i = 0; $i < (50 - $count); $i++) {
                $this->_createSharedTag();
            }
        }
        
        $paging = new Tinebase_Model_Pagination(array('limit' => 50));
        $tags = $this->_instance->searchTags($filter, $paging);
        $count = $this->_instance->getSearchTagsCount($filter);
        
        $this->assertEquals(50, count($tags), 'did not find 50 tags');
        $this->assertGreaterThanOrEqual(50, $count, 'count mismatch');
    }
    
    /**
     * test if the application parameter is used properly
     */
    public function testSearchTagsForApplication()
    {
        $this->_createSharedTag();
        $filter = new Tinebase_Model_TagFilter([
            'application' => 'Addressbook'
        ]);
        $tags = $this->_instance->searchTags($filter);
        $tags = $tags->filter('system_tag', false);
        $this->_instance->deleteTags($tags->getArrayOfIds());

        $this->_createSharedTag(['name' => 'tag1']);
        $this->_createSharedTag(['name' => 'tag2']);

        // this tag should not occur, search is in the addressbook application
        $crmAppId = Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId();
        $this->_createSharedTag(['name' => 'tag3'], array($crmAppId));

        $filter = new Tinebase_Model_TagFilter(array('application' => 'Addressbook'));

        $tags = $this->_instance->searchTags($filter);
        $tags = $tags->filter('system_tag', false);
        $this->assertEquals(2, $tags->count());
    }

    /**
     * tags can be restricted to models of an application
     */
    public function testSearchTagsForModel()
    {
        $salesAppId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
        $crmAppId = Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId();

        $appTag = $this->_createSharedTag(['name' => 'modelTest app'], [$salesAppId]);
        $offerTag = $this->_createSharedTag(['name' => 'modelTest offer'], [$salesAppId]);
        $this->_instance->purgeContexts($offerTag->getId());
        $this->_instance->setContexts([$crmAppId], $offerTag->getId(), [Sales_Model_DocumentPosition_Offer::class]);
        $anyTag = $this->_createSharedTag(['name' => 'modelTest any']);
        $this->_instance->purgeContexts($anyTag->getId());
        $this->_instance->setContexts(['any'], $anyTag->getId(), [Sales_Model_DocumentPosition_Invoice::class]);

        $this->assertEqualsCanonicalizing([$crmAppId, $salesAppId], $this->_instance->getContexts($offerTag->getId()));
        $this->assertSame([Sales_Model_DocumentPosition_Offer::class], $this->_instance->getContextModels($offerTag->getId()));
        $this->assertSame([], $this->_instance->getContextModels($appTag->getId()));
        $this->assertSame([Sales_Model_DocumentPosition_Invoice::class], $this->_instance->getContextModels($anyTag->getId()));

        $search = function (array $filter): array {
            $names = $this->_instance->searchTags(new Tinebase_Model_TagFilter(array_merge($filter, ['name' => 'modelTest'])))
                ->name;
            sort($names);
            return $names;
        };

        // app search without model finds all tags of the app
        $this->assertSame(['modelTest any', 'modelTest app', 'modelTest offer'], $search(['application' => 'Sales']));
        // 'any' with a model restricts the app of the model
        $this->assertSame(['modelTest app', 'modelTest offer'],
            $search(['model' => Sales_Model_DocumentPosition_Offer::class]));
        $this->assertSame(['modelTest any', 'modelTest app'],
            $search(['model' => Sales_Model_DocumentPosition_Invoice::class]));
        $this->assertSame(['modelTest app'], $search(['model' => Sales_Model_Product::class]));
        $this->assertSame(['modelTest any', 'modelTest offer'], $search(['model' => Crm_Model_Lead::class]));
        $this->assertSame(['modelTest any'], $search(['application' => 'Addressbook']));
        // empty application (e.g. from tag combo without app)
        $this->assertSame(['modelTest any', 'modelTest offer'], $search(['application' => '', 'model' => Crm_Model_Lead::class]));
    }

    public function testGetUsableTags()
    {
        $salesAppId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();

        $offerTag = $this->_createSharedTag(['name' => 'usableTest offer'], [$salesAppId]);
        $this->_instance->purgeContexts($offerTag->getId());
        $this->_instance->setContexts([$salesAppId], $offerTag->getId(), [Sales_Model_DocumentPosition_Offer::class]);
        $anyTag = $this->_createSharedTag(['name' => 'usableTest any']);
        $deletedTag = $this->_createSharedTag(['name' => 'usableTest deleted']);
        $this->_instance->deleteTags($deletedTag->getId());

        $tags = [];
        foreach ($this->_instance->getUsableTags() as $tag) {
            $tags[$tag['name']] = $tag;
        }

        $this->assertArrayNotHasKey('usableTest deleted', $tags, 'deleted tags are not usable');
        $this->assertSame([['application_id' => '0', 'model' => '']], $tags['usableTest any']['usage_contexts']);
        $this->assertSame(
            [['application_id' => (string)$salesAppId, 'model' => Sales_Model_DocumentPosition_Offer::class]],
            $tags['usableTest offer']['usage_contexts']
        );
        $this->assertSame($offerTag->color, $tags['usableTest offer']['color']);

        $json = (new Tinebase_Frontend_Json())->getUsableTags();
        $this->assertSame(count($json['results']), $json['totalcount']);
        $this->assertContains($anyTag->getId(), array_column($json['results'], 'id'));
        $this->assertNotContains($deletedTag->getId(), array_column($json['results'], 'id'));
    }

    public function testSetContextsInvalidModel()
    {
        $tag = $this->_createSharedTag(['name' => 'modelTest invalid']);
        $this->expectException(Tinebase_Exception_InvalidArgument::class);
        $this->_instance->setContexts(['any'], $tag->getId(), ['Sales_Model_NotExisting']);
    }

    public function testUpdateTagWithoutRights()
    {
        $sharedTag = $this->_createSharedTag(['name' => 'test'], null, 'sclever');
        $sharedTag['name'] = 'testUpdate';
        $updatedTag = Tinebase_Tags::getInstance()->update($sharedTag);
        $this->assertEquals('testUpdate', $updatedTag['name']);
    }

    public function testSharedTagWithRoleRights()
    {
        $sharedTag = $this->_createSharedTag(['name' => 'test'], null, 'sclever');

        $right = new Tinebase_Model_TagRight(array(
            'tag_id'        => $sharedTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE,
            'account_id'    => Tinebase_Role::getInstance()->getRoleByName('user role'),
            'view_right'    => true,
            'use_right'     => true,
        ));
        $this->_instance->setRights($right);
    }
}
