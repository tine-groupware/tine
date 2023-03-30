# Admin_Frontend_Cli  

cli server for Admin

This class handles cli requests for the Admin  



## Extend:

Tinebase_Frontend_Cli_Abstract

## Methods

| Name | Description |
|------|-------------|
|[cleanupMailaccounts](#admin_frontend_clicleanupmailaccounts)|removes mailaccounts that are no longer linked to a user|
|[copyGroupmembersToDifferentGroup](#admin_frontend_clicopygroupmemberstodifferentgroup)|Add all members from one group to another|
|[createSystemGroupsForAddressbookLists](#admin_frontend_clicreatesystemgroupsforaddressbooklists)|create system groups for addressbook lists that don't have a system group|
|[deleteAccount](#admin_frontend_clideleteaccount)||
|[enableAutoMoveNotificationsinSystemEmailAccounts](#admin_frontend_clienableautomovenotificationsinsystememailaccounts)|enabled sieve_notification_move for all system accounts|
|[getSetEmailAliasesAndForwards](#admin_frontend_cligetsetemailaliasesandforwards)|usage: method=Admin.getSetEmailAliasesAndForwards [-d] [-v] [aliases_forwards.csv] [-- pwlist=pws.csv]|
|[importGroups](#admin_frontend_cliimportgroups)|import groups|
|[importUser](#admin_frontend_cliimportuser)|import users|
|[iterateAddressbookLists](#admin_frontend_cliiterateaddressbooklists)|iterate adb lists|
|[ldapUserSearchQuery](#admin_frontend_clildapusersearchquery)||
|[repairUserSambaoptions](#admin_frontend_clirepairusersambaoptions)|overwrite Samba options for users|
|[setPasswords](#admin_frontend_clisetpasswords)|set passwords for given user accounts (csv with email addresses or username) - random pw is generated if not in csv|
|[setPasswordsFromEmailBackend](#admin_frontend_clisetpasswordsfromemailbackend)|set use pws from email backend (for example dovecot)|
|[shortenLoginnames](#admin_frontend_clishortenloginnames)|shorten loginnmes to fit ad samaccountname|
|[synchronizeGroupAndListMembers](#admin_frontend_clisynchronizegroupandlistmembers)|usage: method=Admin.synchronizeGroupAndListMembers [-d]|
|[updateNotificationScripts](#admin_frontend_cliupdatenotificationscripts)|update notificationScript for all system accounts|

## Inherited methods

| Name | Description |
|------|-------------|
|createContainer|add container|
|createDemoData|create demo data|
|getHelp|echos usage information|
|importegw14|import from egroupware|
|setContainerGrants|set container grants|
|setContainerGrantsReadOnly|setContainerGrantsReadOnly|
|updateImportExportDefinition|update or create import/export definition|



### Admin_Frontend_Cli::cleanupMailaccounts  

**Description**

```php
public cleanupMailaccounts (\Zend_Console_Getopt $opts)
```

removes mailaccounts that are no longer linked to a user 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Admin_Frontend_Cli::copyGroupmembersToDifferentGroup  

**Description**

```php
public copyGroupmembersToDifferentGroup (\Zend_Console_Getopt $opts)
```

Add all members from one group to another 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


<hr />


### Admin_Frontend_Cli::createSystemGroupsForAddressbookLists  

**Description**

```php
public createSystemGroupsForAddressbookLists (\Zend_Console_Getopt $_opts)
```

create system groups for addressbook lists that don't have a system group 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::deleteAccount  

**Description**

```php
 deleteAccount (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::enableAutoMoveNotificationsinSystemEmailAccounts  

**Description**

```php
public enableAutoMoveNotificationsinSystemEmailAccounts (\Zend_Console_Getopt $opts)
```

enabled sieve_notification_move for all system accounts 

usage: method=Admin.enableAutoMoveNotificationsinSystemEmailAccounts [-d] -- [folder=Benachrichtigungen] 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Admin_Frontend_Cli::getSetEmailAliasesAndForwards  

**Description**

```php
public getSetEmailAliasesAndForwards (\Zend_Console_Getopt $opts)
```

usage: method=Admin.getSetEmailAliasesAndForwards [-d] [-v] [aliases_forwards.csv] [-- pwlist=pws.csv] 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::importGroups  

**Description**

```php
public importGroups (\Zend_Console_Getopt $_opts)
```

import groups 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::importUser  

**Description**

```php
public importUser (\Zend_Console_Getopt $_opts)
```

import users 

 

**Parameters**

* `(\Zend_Console_Getopt) $_opts`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::iterateAddressbookLists  

**Description**

```php
public iterateAddressbookLists (\Tinebase_Record_RecordSet $records)
```

iterate adb lists 

 

**Parameters**

* `(\Tinebase_Record_RecordSet) $records`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::ldapUserSearchQuery  

**Description**

```php
 ldapUserSearchQuery (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::repairUserSambaoptions  

**Description**

```php
public repairUserSambaoptions (void)
```

overwrite Samba options for users 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::setPasswords  

**Description**

```php
public setPasswords (\Zend_Console_Getopt $opts)
```

set passwords for given user accounts (csv with email addresses or username) - random pw is generated if not in csv 

usage: method=Admin.setPasswords [-d] [-v] [userlist1.csv] [userlist2.csv] [-- pw=password sendmail=1 pwlist=pws.csv updateaccount=1 ignorepolicy=1]  
  
- sendmail=1 -> sends mail to user with pw  
- pwlist=pws.csv -> creates csv file with the users and their new pws  
- updateaccount=1 -> also updates user-accounts (for example to create user email accounts) 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




**Throws Exceptions**


`\Tinebase_Exception_AccessDenied`


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_NotFound`


<hr />


### Admin_Frontend_Cli::setPasswordsFromEmailBackend  

**Description**

```php
public setPasswordsFromEmailBackend (\Zend_Console_Getopt $opts)
```

set use pws from email backend (for example dovecot) 

usage: method=Admin.setPasswordsFromEmailBackend [-d] 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




<hr />


### Admin_Frontend_Cli::shortenLoginnames  

**Description**

```php
public shortenLoginnames (void)
```

shorten loginnmes to fit ad samaccountname 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::synchronizeGroupAndListMembers  

**Description**

```php
public synchronizeGroupAndListMembers (\Zend_Console_Getopt $opts)
```

usage: method=Admin.synchronizeGroupAndListMembers [-d] 

 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`void`


<hr />


### Admin_Frontend_Cli::updateNotificationScripts  

**Description**

```php
public updateNotificationScripts (\Zend_Console_Getopt $opts)
```

update notificationScript for all system accounts 

usage: method=Admin.updateNotificationScripts [-d] 

**Parameters**

* `(\Zend_Console_Getopt) $opts`

**Return Values**

`int`




**Throws Exceptions**


`\Tinebase_Exception_InvalidArgument`


`\Tinebase_Exception_Record_Validation`


<hr />

