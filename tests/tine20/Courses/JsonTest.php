<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Courses_Frontend_Json
 */
class Courses_JsonTest extends TestCase
{
    /**
     * @var Courses_Frontend_Json
     */
    protected $_json = array();
    
    /**
     * config groups
     * 
     * @var array
     */
    protected $_configGroups = array();
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_groupsToDelete = null;
    
    /**
     * test department
     * 
     * @var Tinebase_Model_Department
     */
    protected $_department = NULL;
    
    /**
     * Student name pattern config
     */
    protected $_schemaConfig;
    
    /**
     * Student username length
     */
    protected $_usernameLengthConfig;
    
    /**
     * The default department
     * @var string
     */
    protected $_defaultDepartmentConfig;
    
    /**
     * @var Boolean
     */
    protected $_schemaConfigChanged = FALSE;
    
    /**
     * @var Boolean
     */
    protected $_usernameLengthConfigChanged = FALSE;
    
     /**
     * @var Boolean
     */
    protected $_defaultDepartmentConfigChanged = FALSE;

    /**
     * @var Boolean
     */
    protected $_additionalGroupMembershipsConfigChanged = FALSE;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        $twigConf = Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG};
        if ($twigConf->{Tinebase_Config::ACCOUNT_TWIG_LOGIN} !==
            '{{ account.accountFirstName|transliterate|removeSpace|accountLoginChars|trim[0:1]|lower }}{{ account.accountLastName|transliterate|removeSpace|accountLoginChars|lower }}'
        ) {
            self::markTestSkipped('test only works with a certain ACCOUNT_TWIG_LOGIN config');
        }

        parent::setUp();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        
        $this->_json = new Courses_Frontend_Json();
        
        $this->_department = Tinebase_Department::getInstance()->create(new Tinebase_Model_Department(array(
            'name'  => Tinebase_Record_Abstract::generateUID()
        )));
        
        $this->_groupsToDelete = new Tinebase_Record_RecordSet('Tinebase_Model_Group');
        foreach (array(
                     Courses_Config::INTERNET_ACCESS_GROUP_ON,
                     Courses_Config::INTERNET_ACCESS_GROUP_FILTERED,
                     Courses_Config::STUDENTS_GROUP
                 ) as $configgroup) {
            $this->_configGroups[$configgroup] = Tinebase_Group::getInstance()->create(new Tinebase_Model_Group(array(
                'name'   => $configgroup
            )));
            $this->_groupsToDelete->addRecord($this->_configGroups[$configgroup]);
            Courses_Config::getInstance()->set($configgroup, $this->_configGroups[$configgroup]->getId());
        }
        
        Courses_Config::getInstance()->set(Courses_Config::SAMBA, new Tinebase_Config_Struct(array(
            'basehomepath' => '\\\\jo\\',
            'homedrive' => 'X:',
            'logonscript_postfix_teacher' => '-lehrer.cmd',
            'logonscript_postfix_member' => '.cmd',
            'baseprofilepath' => '\\\\jo\\profiles\\',
        )));

        // set some complex default pws (to make AD happy)
        $pwConfig = Tinebase_Record_Abstract::generateUID(8) . 'B{]';
        Courses_Config::getInstance()->set(Courses_Config::STUDENT_PASSWORD_SUFFIX, $pwConfig);
        Courses_Config::getInstance()->set(Courses_Config::TEACHER_PASSWORD, $pwConfig);

        $this->_schemaConfig = Courses_Config::getInstance()->get(Courses_Config::STUDENTS_USERNAME_SCHEMA);
        $this->_usernameLengthConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::MAX_USERNAME_LENGTH);
        $this->_defaultDepartmentConfig = Courses_Config::getInstance()->get(Courses_Config::DEFAULT_DEPARTMENT);

        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        $this->_groupIdsToDelete = $this->_groupsToDelete->getArrayOfIds();
        if ($this->_schemaConfigChanged) {
            Courses_Config::getInstance()->set(Courses_Config::STUDENTS_USERNAME_SCHEMA, $this->_schemaConfig);
        }
        
        if ($this->_usernameLengthConfigChanged) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::MAX_USERNAME_LENGTH, $this->_usernameLengthConfig);
        }
        
        if ($this->_defaultDepartmentConfigChanged) {
            Courses_Config::getInstance()->set(Courses_Config::DEFAULT_DEPARTMENT, $this->_defaultDepartmentConfig);
        }

        if ($this->_additionalGroupMembershipsConfigChanged) {
            Courses_Config::getInstance()->set(Courses_Config::ADDITIONAL_GROUP_MEMBERSHIPS, []);
        }

        parent::tearDown();
    }
    
    /**
     * try to add a Course
     */
    public function testAddCourse()
    {
        $courseData = $this->_saveCourse(['members' => [
            ['id' => $this->_personas['sclever']->getId()],
        ]]);
        
        // checks
        $this->assertEquals('blabla', $courseData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseData['created_by'], 'Created by has not been set.');
        $this->assertTrue(! empty($courseData['group_id']));
        $this->assertCount(1, $courseData['members']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);

        // check if it got deleted
        $this->expectException('Tinebase_Exception_NotFound');
        Courses_Controller_Course::getInstance()->get($courseData['id']);
    }
    
    /**
     * try to get a Course
     */
    public function testGetCourse()
    {
        $course = $this->_saveCourse();
        $courseData = $this->_json->getCourse($course['id']);
        
        // checks
        $this->assertEquals($course['description'], $courseData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseData['created_by']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }

    /**
     * try to update a Course
     */
    public function testUpdateCourse()
    {
        $courseData = $this->_saveCourse();

        // update Course
        $courseData['description'] = "blubbblubb";
        $courseData['members'] = array();
        $courseData['type'] = $courseData['type']['value'];
        $courseUpdated = $this->_json->saveCourse($courseData);
        
        // check
        $this->assertEquals($courseData['id'], $courseUpdated['id']);
        $this->assertEquals($courseData['description'], $courseUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $courseUpdated['last_modified_by']);
        $this->assertEquals($courseData['members'], $courseUpdated['members']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }

    protected function _saveCourse(array $additionalCourseData = [])
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse(array_merge($course, $additionalCourseData));
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        return $courseData;
    }

    public function testUpdateCourseSetAdditionalMemberships($checkWithNoRight = false)
    {
        $group = $this->_setAdditionalGroupMembershipsConfig();
        // ldap tweak
        $user = ($checkWithNoRight ? $this->_personas['sclever'] : $this->_personas['pwulf']);
        $courseData = $this->_saveCourse(['members' => [
            ['id' => $user->getId()],
        ]]);
        // add memberships
        $memberships = [
            $group->getId()
        ];
        $courseData['type'] = $courseData['type']['value'];
        $courseData['members'][0]['additionalGroups'] = $memberships;
        $courseUpdated = $this->_json->saveCourse($courseData);
        self::assertTrue(isset($courseUpdated['members'][0]['additionalGroups']));
        if ($checkWithNoRight) {
            self::assertEquals([], $courseUpdated['members'][0]['additionalGroups'],
                print_r($courseUpdated['members'], true));
        } else {
            self::assertEquals($memberships, $courseUpdated['members'][0]['additionalGroups'],
                print_r($courseUpdated['members'], true));
        }

        // add new member - assert that this api returns the additionalGroups, too
        $result = $this->_json->addNewMember(array(
            'accountFirstName' => 'jams',
            'accountLastName'  => 'hot',
            'ad'
        ), $courseUpdated);

        foreach ($result['results'] as $member) {
            if (! $checkWithNoRight && $member['id'] === $user->getId()) {
                self::assertEquals($memberships, $member['additionalGroups'], print_r($member, true));
            } else {
                self::assertEquals([], $member['additionalGroups'], print_r($member, true));
            }
        }

        // remove memberships
        $memberships = [];
        $courseData['members'][0]['additionalGroups'] = $memberships;
        $courseUpdated = $this->_json->saveCourse($courseData);
        self::assertEquals($memberships, $courseUpdated['members'][0]['additionalGroups'], 'memberships should be empty');
    }

    public function testUpdateCourseSetAdditionalMembershipsWithoutRight()
    {
        $this->_skipIfLDAPBackend('fixme');

        // call testUpdateCourseSetAdditionalMemberships without the right
        $this->_removeRoleRight('Courses', Courses_Acl_Rights::SET_ADDITIONAL_MEMBERSHIPS);
        $this->testUpdateCourseSetAdditionalMemberships(true);
    }

    /**
     * try to get a Course
     */
    public function testSearchCourses($queryFilter = false)
    {
        // create
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        
        // search & check
        $filter = $queryFilter
            ? [['field' => 'query',
                'operator' => 'contains',
                'value' => $courseData['name']
            ]]
            : $this->_getCourseFilter($courseData['name']);
        $search = $this->_json->searchCourses($filter, $this->_getPaging());
        $this->assertEquals($course['description'], $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteCourses($courseData['id']);
    }

    public function testSearchCoursesQueryFilter()
    {
        $this->testSearchCourses(true);
    }

    /**
     * test for import of members (1)
     *
     * @group longrunning
     */
    public function testImportMembersIntoCourse1()
    {
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('admin_user_import_csv');
        $result = $this->_importHelper(dirname(dirname(__FILE__)) . '/Admin/files/testHeadline.csv', $definition);

        foreach ($result['members'] as $member) {
            $this->_usernamesToDelete[] = $member['data'];
        }
        $this->assertEquals(3, count($result['members']), print_r($result, TRUE));

        return $result;
    }

    /**
     * test for import of members (1) with Twig
     *
     * @group longrunning
     */
    public function testImportMembersIntoCourseWithTwig()
    {
        // set twig config
        $twigConf = Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG};
        $twigConf->{Tinebase_Config::ACCOUNT_TWIG_DISPLAYNAME} = '{{ account.accountFirstName[0:1]|upper }}{{ account.accountLastName|lower }}';
        //$twigConf->{Tinebase_Config::ACCOUNT_TWIG_FULLNAME} = '{{ account.accountFirstName[0:1]|lower }}{{ account.accountLastName|upper }}';

        /** propper test...
         * $tmpFile = Tinebase_TempFile::getInstance()->createTempFile(dirname(dirname(__FILE__)) . '/Admin/files/testHeadline.csv');
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $result = Courses_Controller_Course::getInstance()->importMembers($tmpFile->getId(), $courseData['id']);*/
        $result = $this->testImportMembersIntoCourse1();

        foreach ($result['members'] as $member) {
            if ('hmoster' === $member['data']) continue;
            $this->assertSame(ucfirst($member['data']), $member['name']);
            $this->assertSame(lcfirst($member['name']), $member['data']);
        }
    }

    /**
     * test for import of members (2)
     * 
     * @group longrunning
     */
    public function testImportMembersIntoCourse2()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/import.txt');
        
        $this->assertEquals(4, count($result['members']), print_r($result, TRUE));
        
        // find philipp lahm
        $lahm = array();
        foreach ($result['members'] as $member) {
            if ($member['name'] == 'Plahm') {
                $lahm = $member;
            }
        }
        $this->assertTrue(! empty($lahm));
        $this->assertEquals('plahm', $lahm['data']);
        
        // get user and check email
        $maildomain = TestServer::getPrimaryMailDomain();
        $user = Tinebase_User::getInstance()->getFullUserById($lahm['id']);
        $this->assertEquals('plahm', $user->accountLoginName);
        $this->assertEquals('plahm@' . $maildomain, $user->accountEmailAddress);
        $this->assertEquals('//base/school/' . $result['name'] . '/' . $user->accountLoginName, $user->accountHomeDirectory);
        $defaultGroupMembers = Tinebase_Group::getInstance()->getGroupMembers(
            Tinebase_Group::getInstance()->getDefaultGroup()->getId(), false
        );
        $this->assertFalse(in_array($user->getId(), $defaultGroupMembers),
            'user not added to default user group. memberships: ' . print_r($defaultGroupMembers, true));

        $courseGroupMembers = Tinebase_Group::getInstance()->getGroupMembers($result['group_id'], false);
        $this->assertTrue(in_array($user->getId(), $courseGroupMembers),
            'user not added to course group. memberships: ' . print_r($courseGroupMembers, true));
    }
    
    /**
     * test for import of members (3) / json import
     *
     * @group longrunning
     */
    public function testImportMembersIntoCourse3()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/import.txt', NULL, TRUE);
        $this->assertEquals(4, count($result['members']), 'import failed');
        $this->assertEquals(4, count(Tinebase_Group::getInstance()->getGroupMembers($this->_configGroups[Courses_Config::STUDENTS_GROUP])), 'imported users not added to students group');

        // the created export should be attached as attachment
        $this->assertCount(1, $result['attachments']);
    }

    /**
     * test for import of members (4) / json import
     * 
     * @see 0006672: allow to import (csv) files with only CR linebreaks
     */
    public function testImportMembersIntoCourse4()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/testklasse.csv',
            $this->_getCourseImportDefinition2(), TRUE);
        $this->assertEquals(7, count($result['members']), 'import failed: ' . print_r($result['members'], true));
        $found = FALSE;
        $user = new Tinebase_Model_FullUser([
            'accountFirstName' => 'Anastasia',
            'accountLastName' => 'Frantasia',
        ], true);
        $user->applyTwigTemplates();

        foreach($result['members'] as $member) {
            if ($member['name'] === $user->accountDisplayName && $member['data'] === 'afrantasia') {
                $found = TRUE;
            }
        }
        $this->assertTrue($found, 'Member "' . $user->accountDisplayName . '" not found in result: '
            . print_r($result['members'], TRUE));
        $this->assertEquals(7, count(Tinebase_Group::getInstance()->getGroupMembers($this->_configGroups[Courses_Config::STUDENTS_GROUP])),
            'imported users not added to students group');
    }

    /**
     * test for import of members (5) / json import
     * 
     * @see 0006942: group memberships and login shell missing for new users
     *
     * @group longrunning
     */
    public function testImportMembersIntoCourse5()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/tah2a.txt', $this->_getCourseImportDefinition3('iso-8859-1'), TRUE);
        $this->assertEquals(2, count($result['members']), 'import failed');
        
        // check group memberships
        $userId = NULL;
        foreach ($result['members'] as $result) {
            if ($result['name'] === 'Uuffbass') {
                $userId = $result['id'];
            }
        }
        $this->assertTrue($userId !== NULL);
        
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertEquals(4, count($groupMemberships), 'new user should have 4 group memberships');
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), $userId . ' not member of the internet group ' . print_r($groupMemberships, TRUE));
        
        $user = Tinebase_User::getInstance()->getFullUserById($userId);
        $this->assertEquals('/bin/false', $user->accountLoginShell);
    }

    public function testImportMembersIntoCourse6()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/duppletten.txt');
        $this->assertEquals(2, count($result['members']), 'import failed');

        $maler = false;
        $maler1 = false;
        foreach ($result['members'] as $result) {
            if ($result['data'] === 'mhans') {
                $maler = true;
            } elseif ($result['data'] === 'mhans01') {
                $maler1 = true;
            }
        }

        $this->assertTrue($maler && $maler1, 'could not find maler and maler01');
    }
    
    /**
     * testGetCoursesPreferences
     * 
     * @see 0006436: Courses preferences do not work (in pref panel)
     */
    public function testGetCoursesPreferences()
    {
        $tinebaseJson = new Tinebase_Frontend_Json();
        $coursesPrefs = $tinebaseJson->searchPreferencesForApplication('Courses', array());
        
        $this->assertTrue($coursesPrefs['totalcount'] > 0);
        $pref = $coursesPrefs['results'][0];
        
        $this->assertEquals(Tinebase_Preference_Abstract::DEFAULTPERSISTENTFILTER, $pref['name']);
        $this->assertGreaterThanOrEqual(2, count($pref['options']));
    }

    /**
     * testImportWithMissingList
     * 
     * @see 0007460: check existence of group/list before user import
     *
     * @group longrunning
     */
    public function testImportWithMissingList()
    {
        $result = $this->_importHelper(dirname(__FILE__) . '/files/tah2a.txt', $this->_getCourseImportDefinition3('iso-8859-1'), TRUE, TRUE);
        $this->assertEquals(2, count($result['members']), 'import failed');
    }
    
    /**
     * test internet access on/off/filtered
     * 
     * @todo remove some code duplication
     */
    public function testInternetAccess()
    {
        $this->_skipIfLDAPBackend('fixme');

        // create new course with internet access
        $courseData = $this->_saveCourse(['members' => [
            ['id' => $this->_personas['sclever']->getId()],
        ]]);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        $userId = $courseData['members'][0]['id'];
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), $userId . ' not member of the internet group ' . print_r($groupMemberships, TRUE));
        
        // filtered internet access
        $courseData['internet'] = 'FILTERED';
        $courseData['type'] = $courseData['type']['value'];
        $courseData = $this->_json->saveCourse($courseData);
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_FILTERED]->getId(), $groupMemberships), 'not member of the filtered internet group ' . print_r($groupMemberships, TRUE));
        $this->assertFalse(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), 'member of the internet group ' . print_r($groupMemberships, TRUE));
        
        // remove internet access
        $courseData['internet'] = 'OFF';
        $courseData['type'] = $courseData['type']['value'];
        $courseData = $this->_json->saveCourse($courseData);
        $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($userId);
        $this->assertFalse(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $groupMemberships), 'member of the internet group ' . print_r($groupMemberships, TRUE));
        $this->assertFalse(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_FILTERED]->getId(), $groupMemberships), 'member of the filtered internet group ' . print_r($groupMemberships, TRUE));
    }
    
    /**
     * testAddNewMember
     * 
     * @see 0006372: add new course member with a button
     * @see 0006878: set primary group for manually added users
     *
     * @group longrunning
     */
    public function testAddNewMember()
    {
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        
        $result = $this->_json->addNewMember(array(
            'accountFirstName' => 'jams',
            'accountLastName'  => 'hot',
        ), $courseData);
        
        $this->assertEquals(1, count($result['results']));
        
        $id = NULL;
        foreach ($result['results'] as $result) {
            if ($result['name'] === 'Jhot') {
                $id = $result['id'];
            }
        }
        $this->assertTrue($id !== NULL);
        
        $newUser = Tinebase_User::getInstance()->getFullUserById($id);
        $this->assertEquals('jhot', $newUser->accountLoginName);
        $this->assertEquals('/bin/false', $newUser->accountLoginShell);
        
        $newUserMemberships = Tinebase_Group::getInstance()->getGroupMemberships($newUser);
        
        $this->assertEquals(4, count($newUserMemberships), 'new user should have 4 group memberships');
        $this->assertTrue(in_array(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $newUserMemberships),
            'could not find default group in memberships: ' . print_r($newUserMemberships, TRUE));
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::INTERNET_ACCESS_GROUP_ON]->getId(), $newUserMemberships),
            $id . ' not member of the internet group ' . print_r($newUserMemberships, TRUE));
        $this->assertTrue(in_array($this->_configGroups[Courses_Config::STUDENTS_GROUP]->getId(), $newUserMemberships),
            $id . ' not member of the students group ' . print_r($newUserMemberships, TRUE));
    }
    
    /**
     * testApplySambaSettings
     * 
     * @see 0006910: new manual users have no samba settings
     */
    public function testApplySambaSettings()
    {
        $user = Tinebase_Core::getUser();
        $config = Courses_Config::getInstance()->samba;
        $profilePath = $config->baseprofilepath . 'school' . '\\' . 'coursexy' . '\\';
        $user->applyOptionsAndGeneratePassword(array('samba' => array(
            'homePath'      => $config->basehomepath,
            'homeDrive'     => $config->homedrive,
            'logonScript'   => 'coursexy' . $config->logonscript_postfix_member,
            'profilePath'   => $profilePath,
            'pwdCanChange'  => new Tinebase_DateTime('@1'),
            'pwdMustChange' => new Tinebase_DateTime('@1')
        )));

        // check samba settings
        $this->assertEquals($profilePath . $user->accountLoginName, $user->sambaSAM->profilePath);
    }
    
    /**
     * Test students loginname with schema 3
     *
     * @group longrunning
     */
    public function testStudentNameSchema3()
    {
        $this->_schemaConfigChanged = true;
        Courses_Config::getInstance()->set(Courses_Config::STUDENTS_USERNAME_SCHEMA, 3);
        
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        
        $result = $this->_json->addNewMember(array(
            'accountFirstName' => 'jams',
            'accountLastName'  => 'hot',
        ), $courseData);
        
        $this->assertEquals(1, count($result['results']));
        
        $id = NULL;
        foreach ($result['results'] as $result) {
            if ($result['name'] === 'Jhot') {
                $id = $result['id'];
            }
        }
        $this->assertTrue($id !== NULL);
        
        $newUser = Tinebase_User::getInstance()->getFullUserById($id);
        $this->assertEquals('jhot', $newUser->accountLoginName);
    }
    
    /**
     * Test students loginname with schema 3 with SpecialChars
     *
     * @group longrunning
     */
    public function testStudentNameSchemaSpecialChars()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // fails in AD setup (only with all tests):
            // Zend_Ldap_Exception: 0x15 (Invalid syntax; 0000200B: objectclass_attrs:
            // attribute 'primarygroupid' on entry 'cn=mycourse44 Lehrer,cn=Users,dc=example,dc=org'
            // contains at least one invalid value!): updating: cn=mycourse44 Lehrer,cn=Users,dc=example,dc=org
            $this->markTestSkipped('skipped for ad backend');
        }

        $this->_schemaConfigChanged = true;
        Courses_Config::getInstance()->set(Courses_Config::STUDENTS_USERNAME_SCHEMA, 3);
        
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        
        $result = $this->_json->addNewMember(array(
                'accountFirstName' => 'Ütmür',
                'accountLastName'  => 'Höt',
        ), $courseData);
        
        $this->assertEquals(1, count($result['results']), 'should add 2 new members');
        
        $id = NULL;
        foreach ($result['results'] as $result) {
            if ($result['name'] === 'Ühöt') {
                $id = $result['id'];
            }
        }
        $this->assertTrue($id !== NULL);
    
        $newUser = Tinebase_User::getInstance()->getFullUserById($id);
        $this->assertEquals('uhoet', $newUser->accountLoginName);
    }
    
    /**
     * Test students loginname with schema set max length
     *
     * @group longrunning
     */
    public function testStudentNameSchemaMaxLength()
    {
        $this->_schemaConfigChanged = true;
        $this->_usernameLengthConfigChanged = true;
        
        Courses_Config::getInstance()->set(Courses_Config::STUDENTS_USERNAME_SCHEMA, 3);
        Tinebase_Config::getInstance()->set(Tinebase_Config::MAX_USERNAME_LENGTH, 4);
        
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        
        $result = $this->_json->addNewMember(array(
                'accountFirstName' => 'Jams',
                'accountLastName'  => 'Hot',
        ), $courseData);
        $this->assertEquals(1, count($result['results']));
        $id = NULL;
        foreach ($result['results'] as $result) {
            if ($result['name'] === 'Hot, Jams') {
                $id = $result['id'];
            }
        }
        $this->assertTrue($id !== NULL);
        
        $newUser = Tinebase_User::getInstance()->getFullUserById($id);
        $this->assertEquals('jhot', $newUser->accountLoginName);
    }
    
    /**
     * Test if the default department is returned
     */
    public function testDefaultDepartment()
    {
        $this->_defaultDepartmentConfigChanged = true;
        Courses_Config::getInstance()->set(Courses_Config::DEFAULT_DEPARTMENT, $this->_department->name);
        $result = $this->_json->getRegistryData();
        $this->assertEquals($this->_department->id, $result['defaultType']['value']);
    }

    /**
     * Test if the ADDITIONAL_GROUP_MEMBERSHIPS groups are in registry data
     */
    public function testAdditionalGroupMembershipsInRegistryData()
    {
        $group = $this->_setAdditionalGroupMembershipsConfig();
        $result = $this->_json->getRegistryData();
        $this->assertTrue(isset($result['additionalGroupMemberships']));
        $this->assertEquals(1, count($result['additionalGroupMemberships']));
        $this->assertEquals($group->getId(), $result['additionalGroupMemberships'][0]['id']);
    }

    /************ protected helper funcs *************/

    protected function _setAdditionalGroupMembershipsConfig()
    {
        $group = $this->_createGroup();
        $this->_additionalGroupMembershipsConfigChanged = true;
        Courses_Config::getInstance()->set(Courses_Config::ADDITIONAL_GROUP_MEMBERSHIPS, [$group->getId()]);
        return $group;
    }

    /**
     * get Course
     *
     * @return array
     */
    protected function _getCourseData()
    {
        return array(
            'name'          => 'mycourse' . rand(0, 100),
            'description'   => 'blabla',
            'type'          => $this->_department->getId(),
            'internet'      => 'ON',
        );
    }
        
    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'creation_time',
            'dir' => 'ASC',
        );
    }

    /**
     * get Course filter
     *
     * @return array
     */
    protected function _getCourseFilter($_courseName)
    {
        return array(
            array(
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => $_courseName
            ),
        );
    }
    
    /**
     * import file
     * 
     * @param string $_filename
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param boolean $_useJsonImportFn
     * @param boolean $removeGroupList
     * @return array course data
     */
    protected function _importHelper($_filename, Tinebase_Model_ImportExportDefinition $_definition = NULL, $_useJsonImportFn = FALSE, $removeGroupList = FALSE)
    {
        $definition = ($_definition !== NULL) ? $_definition : $this->_getCourseImportDefinition();
        
        $course = $this->_getCourseData();
        $courseData = $this->_json->saveCourse($course);
        $this->_groupsToDelete->addRecord(Tinebase_Group::getInstance()->getGroupById($courseData['group_id']));
        
        if ($removeGroupList) {
            $group = Admin_Controller_Group::getInstance()->get($courseData['group_id']);
            Addressbook_Controller_List::getInstance()->delete($group->list_id);
        }
        
        if ($_useJsonImportFn) {
            $tempFileBackend = new Tinebase_TempFile();
            $tempFile = $tempFileBackend->createTempFile($_filename);
            Courses_Config::getInstance()->set(Courses_Config::STUDENTS_IMPORT_DEFINITION, $definition->name);
            $result = $this->_json->importMembers($tempFile->getId(), $courseData['group_id'], $courseData['id']);
            
            $this->assertGreaterThan(0, $result['results']);
            
        } else {
            $maildomain = TestServer::getPrimaryMailDomain();
            
            $importer = call_user_func($definition->plugin . '::createFromDefinition', $definition, array(
                    'group_id'                  => $courseData['group_id'],
                    'accountHomeDirectoryPrefix' => '//base/school/' . $courseData['name'] . '/',
                    'accountEmailDomain'        => $maildomain,
                    'password'                  => $courseData['name'],
                    'samba'                     => array(
                        'homePath'    => '//basehome/',
                        'homeDrive'   => 'H:',
                        'logonScript' => 'logon.bat',
                        'profilePath' => '\\\\profile\\',
                    ),
                    'encoding' => 'UTF-8',
                    'afterAccountLoginName'         => function(Tinebase_Model_FullUser $user) {
                        $count = 1;
                        $shortUsername = $user->shortenUsername(2);
                        while ($count < 100) {
                            try {
                                Tinebase_User::getInstance()->getUserByLoginName($user->accountLoginName);
                                $user->accountLoginName = $shortUsername . sprintf('%02d', $count++);
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                break;
                            }
                        }
                        if ($count > 1) {
                            $user->accountEmailAddress = null;
                            $user->applyTwigTemplates();
                        }

                        $count = 1;
                        $accountFullName = $user->accountFullName;
                        while ($count < 100) {
                            try {
                                Tinebase_User::getInstance()->getUserByProperty('accountFullName', $user->accountFullName);
                                $user->accountFullName = $accountFullName . sprintf('%02d', $count++);
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                break;
                            }
                        }
                    },
                )
            );
            $tempFilename = TestServer::replaceEmailDomainInFile($_filename);
            $importer->importFile($tempFilename);
        }
        $courseData = $this->_json->getCourse($courseData['id']);

        return $courseData;
    }
    
    /**
     * returns course import definition
     * 
     * @return Tinebase_Model_ImportExportDefinition
     */
    protected function _getCourseImportDefinition()
    {
        try {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('course_user_import_csv');
        } catch (Tinebase_Exception_NotFound $e) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->create(new Tinebase_Model_ImportExportDefinition(array(
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Admin')->getId(),
                    'name'              => 'course_user_import_csv',
                    'type'              => 'import',
                    'model'             => 'Tinebase_Model_FullUser',
                    'plugin'            => 'Admin_Import_User_Csv',
                    'plugin_options'    => '<?xml version="1.0" encoding="UTF-8"?>
            <config>
                <headline>1</headline>
                <use_headline>0</use_headline>
                <dryrun>0</dryrun>
                <encoding>UTF-8</encoding>
                <delimiter>;</delimiter>
                <mapping>
                    <field>
                        <source>lastname</source>
                        <destination>accountLastName</destination>
                    </field>
                    <field>
                        <source>firstname</source>
                        <destination>accountFirstName</destination>
                    </field>
                </mapping>
            </config>')
            ));
        }
        
        return $definition;
    }
    
     /**
     * returns course import definition
     * 
     * @return Tinebase_Model_ImportExportDefinition
     */
    protected function _getCourseImportDefinition2()
    {
        try {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('course_user_import_csv2');
        } catch (Tinebase_Exception_NotFound $e) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->create(new Tinebase_Model_ImportExportDefinition(array(
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Admin')->getId(),
                    'name'              => 'course_user_import_csv2',
                    'type'              => 'import',
                    'model'             => 'Tinebase_Model_FullUser',
                    'plugin'            => 'Admin_Import_User_Csv',
                    'plugin_options'    => '<?xml version="1.0" encoding="UTF-8"?>
            <config>
                <headline>1</headline>
                <use_headline>0</use_headline>
                <dryrun>0</dryrun>
                <delimiter>;</delimiter>
                <mapping>
                    <field>
                        <source>VORNAME</source>
                        <destination>accountFirstName</destination>
                    </field>
                    <field>
                        <source>NAME</source>
                        <destination>accountLastName</destination>
                    </field>
                    </mapping>
            </config>')
            ));
        }
        
        return $definition;
    }
    
    /**
     * returns course import definition
     * 
     * @param string $encoding
     * @return Tinebase_Model_ImportExportDefinition
     */
    protected function _getCourseImportDefinition3($encoding = 'UTF-8')
    {
        try {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('course_user_import_csv');
        } catch (Tinebase_Exception_NotFound $e) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->create(new Tinebase_Model_ImportExportDefinition(array(
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Admin')->getId(),
                    'name'              => 'course_user_import_csv',
                    'type'              => 'import',
                    'model'             => 'Tinebase_Model_FullUser',
                    'plugin'            => 'Admin_Import_User_Csv',
                    'plugin_options'    => '<?xml version="1.0" encoding="UTF-8"?>
            <config>
                <headline>1</headline>
                <use_headline>0</use_headline>
                <dryrun>0</dryrun>
                <encoding>' . $encoding . '</encoding>
                <delimiter>;</delimiter>
                <mapping>
                    <field>
                        <source>Name</source>
                        <destination>accountLastName</destination>
                    </field>
                    <field>
                        <source>Vorname</source>
                        <destination>accountFirstName</destination>
                    </field>
                </mapping>
            </config>')
            ));
        }

        return $definition;
    }
}
