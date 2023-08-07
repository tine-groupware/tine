<?php
/**
* Tine 2.0 - http://www.tine20.org
*
* @package     ExampleApplication
* @subpackage  Test
* @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
* @author      Stefanie Stamer <s.stamer@metaways.de>
*/

/**
* Test helper
*/
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
* Test class for ExampleApplication_JsonTest
*/
class ExampleApplication_ControllerTest extends ExampleApplication_TestCase
{
    public function testEventDispatching()
    {
        $observable = new ExampleApplication_Model_ExampleRecord(array('id' => 'testIdentifier'), true);

        $observer = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => 'ExampleApplication_Model_ExampleRecord',
            'observable_identifier' => 'testIdentifier',
            'observer_model'        => 'ExampleApplication_Model_ExampleRecord',
            'observer_identifier'   => 'exampleIdentifier',
            'observed_event'        => 'Tinebase_Event_Record_Update'
        ));

        $observerController = Tinebase_Record_PersistentObserver::getInstance();

        $observerController->addObserver($observer);

        ob_start();
        ob_clean();
        $event = new Tinebase_Event_Record_Update();
        $event->observable = $observable;
        $observerController->fireEvent($event);
        $result = ob_get_clean();

        $this->assertEquals('catched record update for observing id: exampleIdentifier', $result);
    }

    public function testExternalDb()
    {
        $exampleRecord = $this->_getExampleRecord();
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->create($exampleRecord);
        try {
            ExampleApplication_Controller_ExternalDbRecord::getInstance()->get($exampleRecord->getId());
            $this->fail('we should be on a different connection => not set uncommited insert');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        try {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $externalRecord = ExampleApplication_Controller_ExternalDbRecord::getInstance()->get($exampleRecord->getId());
        } finally {
            Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . ExampleApplication_Model_ExampleRecord::TABLE_NAME,
                'id = "' . $exampleRecord->getId() . '"');
        }

        $this->assertSame($exampleRecord->getId(), $externalRecord->getId());
    }

    public function testDateFilter()
    {
        $exampleRecord = $this->_getExampleRecord();
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->create($exampleRecord);
        static::assertNull($exampleRecord->datetime);

        $result = ExampleApplication_Controller_ExampleRecord::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(ExampleApplication_Model_ExampleRecord::class,
            [['field' => 'datetime', 'operator' => 'before', 'value' => Tinebase_DateTime::now()]])
        );
        static::assertSame(1, $result->count());

        $result = ExampleApplication_Controller_ExampleRecord::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(ExampleApplication_Model_ExampleRecord::class,
                [['field' => 'datetime', 'operator' => 'after', 'value' => Tinebase_DateTime::now()]])
        );
        static::assertSame(1, $result->count());
    }

    public function testPerspective()
    {
        /** @var ExampleApplication_Model_ExampleRecord $exampleRecord */
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->create($this->_getExampleRecord());
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->setPerspectiveTo($this->_personas['sclever']);
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(ExampleApplication_Model_ExampleRecord::class, [
            ['field' => 'id', 'operator' => 'equals', 'value' => $exampleRecord->getId()],
        ]);
        /** @var ExampleApplication_Model_ExampleRecord $exampleRecord */
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($exampleRecord);
        $this->assertSame([], $exampleRecord->getPerspectiveData(ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE));
        $this->assertSame([$exampleRecord->getId() => null],
            ExampleApplication_Controller_ExampleRecord::getInstance()->search($filter, null, false,
                ['id', ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE]));
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE} = true;
        $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT} = '';
        $exampleRecord->setPerspectiveTo($this->_personas['sclever']);
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE} = true;

        /** @var ExampleApplication_Model_ExampleRecord $exampleRecord */
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($exampleRecord);
        $this->assertSame([], $exampleRecord->getPerspectiveData(ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE));
        $this->assertSame([$exampleRecord->getId() => null],
            ExampleApplication_Controller_ExampleRecord::getInstance()->search($filter, null, false,
                ['id', ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE]));
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->setPerspectiveTo($this->_personas['sclever']);
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE} = false;
        $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT} = Tinebase_DateTime::now();
        $exampleRecord->setPerspectiveTo($this->_personas['jmcblack']);
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});

        /** @var ExampleApplication_Model_ExampleRecord $exampleRecord */
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($exampleRecord);
        $this->assertSame([
            $exampleRecord->getPerspectiveKey($this->_personas['sclever']) => false,
        ], $exampleRecord->getPerspectiveData(ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE));
        $this->assertSame([$exampleRecord->getId() => json_encode([
                $exampleRecord->getPerspectiveKey($this->_personas['sclever']) => false,
            ])], ExampleApplication_Controller_ExampleRecord::getInstance()->search($filter, null, false,
                ['id', ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE]));
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->setPerspectiveTo($this->_personas['sclever']);
        $this->assertSame(false, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertInstanceOf(Tinebase_DateTime::class, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
        $exampleRecord->setPerspectiveTo($this->_personas['jmcblack']);
        $this->assertSame(true, $exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSPECTIVE});
        $this->assertNull($exampleRecord->{ExampleApplication_Model_ExampleRecord::FLD_PERSP_DT});
    }

    public function testOneToOne()
    {
        $record = $this->_getExampleRecord();
        $record->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE} = [
            ExampleApplication_Model_OneToOne::FLD_NAME => 'unittest'
        ];

        $createdRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->create($record);
        $expander = new Tinebase_Record_Expander(ExampleApplication_Model_ExampleRecord::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE => []
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$createdRecord]));

        static::assertTrue($createdRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE} instanceof
            ExampleApplication_Model_OneToOne, 'onetoone not instance of ' . ExampleApplication_Model_OneToOne::class);
        static::assertNull($createdRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD});

        $createdRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD} = Addressbook_Controller_Contact::getInstance()
            ->get($this->_personas['sclever']->contact_id)->toArray();

        $updatedRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($createdRecord);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$updatedRecord]));

        static::assertSame($this->_personas['sclever']->contact_id, $updatedRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD});

        $updatedRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_NAME} = 'unittestUpdate';
        $updatedRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($updatedRecord);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$updatedRecord]));

        static::assertSame($this->_personas['sclever']->contact_id, $updatedRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD});
        static::assertSame('unittestUpdate', $updatedRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_NAME});

        // empty string means nulling the field
        $updatedRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD} = '';
        $updatedRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($updatedRecord);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$updatedRecord]));

        static::assertNull($updatedRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD});

        $updatedRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD} = $this->_personas['pwulf']->contact_id;

        $updatedRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($updatedRecord);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$updatedRecord]));

        static::assertSame($this->_personas['pwulf']->contact_id, $updatedRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_ADB_RECORD});

        // dependent record should be deleted when it is not a record 
        $updatedRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE} = '';
        $updatedRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($updatedRecord);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$updatedRecord]));

        static::assertNull($updatedRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE});

        // dependent record should be created again
        $updatedRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE} = new ExampleApplication_Model_OneToOne([
            ExampleApplication_Model_OneToOne::FLD_EXAMPLE_RECORD => $updatedRecord->getId(),
            ExampleApplication_Model_OneToOne::FLD_NAME => 'recreate test'
        ]);

        $updatedRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->update($updatedRecord);
        Tinebase_Record_Expander_DataRequest::clearCache();
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$updatedRecord]));

        static::assertSame('recreate test', $updatedRecord
            ->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE}
            ->{ExampleApplication_Model_OneToOne::FLD_NAME}, 'dependent record should be created again');
    }
}
