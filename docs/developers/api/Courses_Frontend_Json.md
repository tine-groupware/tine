# Courses_Frontend_Json  

This class handles all Json requests for the Courses application

## Implements:
Tinebase_Frontend_Json_Interface, Tinebase_Frontend_Interface

## Extend:

Tinebase_Frontend_Json_Abstract

## Methods

| Name | Description |
|------|-------------|
|[__construct](#courses_frontend_json__construct)|the constructor|
|[addNewMember](#courses_frontend_jsonaddnewmember)|add new member to course|
|[deleteCourses](#courses_frontend_jsondeletecourses)|deletes existing records|
|[getCourse](#courses_frontend_jsongetcourse)|Return a single record|
|[importMembers](#courses_frontend_jsonimportmembers)|import course members|
|[resetPassword](#courses_frontend_jsonresetpassword)|reset password for given account - call Admin_Frontend_Json::resetPassword()|
|[saveCourse](#courses_frontend_jsonsavecourse)|creates/updates a record|
|[searchCourseTypes](#courses_frontend_jsonsearchcoursetypes)|Search for records matching given arguments|
|[searchCourses](#courses_frontend_jsonsearchcourses)|Search for records matching given arguments|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|magic method for json api|
|addFilterModelPlugin|Add a plugin for a former filter|
|getModelsConfiguration|returns model configurations for application starter|
|getRegistryData|Returns registry data of the application.|
|getRelatableModels|Returns all relatable models for this app|
|getTemplates|get available templates by containerId|
|resolveContainersAndTags|resolve containers and tags|



### Courses_Frontend_Json::__construct  

**Description**

```php
public __construct (void)
```

the constructor 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Courses_Frontend_Json::addNewMember  

**Description**

```php
public addNewMember (array $userData, array $courseData)
```

add new member to course 

 

**Parameters**

* `(array) $userData`
* `(array) $courseData`

**Return Values**

`array`




<hr />


### Courses_Frontend_Json::deleteCourses  

**Description**

```php
public deleteCourses (array $ids)
```

deletes existing records 

 

**Parameters**

* `(array) $ids`

**Return Values**

`array`




<hr />


### Courses_Frontend_Json::getCourse  

**Description**

```php
public getCourse (string $id)
```

Return a single record 

 

**Parameters**

* `(string) $id`

**Return Values**

`array`

> record data


<hr />


### Courses_Frontend_Json::importMembers  

**Description**

```php
public importMembers (string $tempFileId, string $groupId, string $courseName)
```

import course members 

 

**Parameters**

* `(string) $tempFileId`
* `(string) $groupId`
* `(string) $courseName`

**Return Values**

`void`


<hr />


### Courses_Frontend_Json::resetPassword  

**Description**

```php
public resetPassword (array $account, string $password, bool $mustChange)
```

reset password for given account - call Admin_Frontend_Json::resetPassword() 

 

**Parameters**

* `(array) $account`
: data of Tinebase_Model_FullUser or account id  
* `(string) $password`
: the new password  
* `(bool) $mustChange`

**Return Values**

`array`




<hr />


### Courses_Frontend_Json::saveCourse  

**Description**

```php
public saveCourse (array $recordData)
```

creates/updates a record 

 

**Parameters**

* `(array) $recordData`

**Return Values**

`array`

> created/updated record


<hr />


### Courses_Frontend_Json::searchCourseTypes  

**Description**

```php
public searchCourseTypes (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />


### Courses_Frontend_Json::searchCourses  

**Description**

```php
public searchCourses (array $filter, array $paging)
```

Search for records matching given arguments 

 

**Parameters**

* `(array) $filter`
* `(array) $paging`

**Return Values**

`array`




<hr />

