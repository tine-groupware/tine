# Tinebase CLI

This document describes the usage of the tine Groupware CLI (`tine20.php`).

## Tinebase.undo

undo changes made by users to records or un-delete them:

php tine20.php --method=Tinebase.undo -d -- \
record_type=Addressbook_Model_Contact \
modification_time=2013-05-08 \
modification_account=ACCOUNTID

## triggerAsyncEvents

### description
Used to trigger asynchronus events like sending of alarm notifications. Can be called by a cronjob and does not need a username (it checks the cronuserid config).

### usage example
php tine20.php --method Tinebase.triggerAsyncEvents

### params
(--username user [optional])


## clearTable

### description
Used to clear obsolete data from tables.

the following tables are supported atm:
- credential_cache
- access_log
- async_job

### usage example
php tine20.php --username tine20admin --method Tinebase.clearTable credential_cache access_log -- date=2010-09-17

One can setup a cron job with dynamically created date by using linux 'date' command and providing a password.
Mind the usage of quotes as provided in the example below. This allows the usage of special characters like '@' in username and/or password.

### usage example (keep last 30 days)
php tine20.php --username "tine20admin" --password "tine20password" --method Tinebase.clearTable access_log -- date=$(date -d -30days +%Y-%m-%d)

### params
(tablenames)
(date, only useful if table = access_log [optional])


## purgeDeletedRecords

### description
Used to purge deleted records from tables (is_deleted == 1).

you can call it with specific tables or without tables to purge records from all installed application tables.

if date param is given, all records that were deleted before the given date (deleted_time) are purged.

### usage example
php tine20.php --username tine20admin --method Tinebase.purgeDeletedRecords -- date=2010-09-17

One can setup a cron job with dynamically created date by using linux 'date' command and providing a password.
Mind the usage of quotes as provided in the example below. This allows the usage of special characters like '@' in username and/or password.

### usage example (keep last 30 days)
php tine20.php --username "tine20admin" --password "tine20password" --method Tinebase.purgeDeletedRecords -- date=$(date -d -30days +%Y-%m-%d)

### params
(tablenames [optional])
(date [optional])


## cleanModlog

### description
Clean timemachine_modlog for records that have been pruned (not deleted!).

### usage example
php tine20.php --username tine20admin --method Tinebase.cleanModlog


## cleanRelations

### description
Clean relations, set relation to deleted if at least one of the ends has been set to deleted or pruned.

### usage example
php tine20.php --username tine20admin --method Tinebase.cleanRelations


## addCustomfield

### description
Used to add new customfields with acl for anyone by default.

### usage examples >= Joey (2011/11)
php --username unittest --method Tinebase.addCustomfield -- application="Addressbook" \
model="Addressbook_Model_Contact"  name="datefield" \
definition='{"label":"Date","type":"Date","uiconfig":{"xtype":"datefield"}}'

php  --username unittest --method Tinebase.addCustomfield -- application="Addressbook" \
model="Addressbook_Model_Contact" name="definedkeyfield" \
definition='{"label":"definedkeyfield","type":"keyField", "keyFieldConfig": {"value": {"records": [{"id": "LOW", "value": "Low"}, {"id": "MID", "value": "Mid"}, {"id": "HIGH", "value": "High"}], "default": "MID"}}}'

### params >= Joey (2011/11)
- application (the application of the cf)
- model (the model which contains the cf, for example Addressbook_Model_Contact)
- name (internal name of the cf)
- definition (json encoded definition of the cf)
  -- label (label in the cf panel)
  -- type (string|int|DateTime|keyField|...)
  -- value_search (if this is 1, the cf becomes a combobox with a list of all values that are saved in the db for this cf)
  -- uiconfig:
  --- xtype (extjs xtype of the cf)
  --- order (for sorting, highest numbers are at the bottom)
  --- group (for grouping boxes)

### usage example < Joey (2011/11)
php tine20.php --username tine20admin --method Tinebase.addCustomfield -- application="Addressbook" name="datefield" label="Date" model="Addressbook_Model_Contact" type="datefield"

### params < Joey (2011/11)
- application (the application of the cf)
- name (internal name of the cf)
- label (label in the cf panel)
- model (the model which contains the cf, for example Addressbook_Model_Contact)
- type (extjs xtype of the cf)
- value_search (if this is 1, the cf becomes a combobox with a list of all values that are saved in the db for this cf)
- order (for sorting, highest numbers are at the bottom)

### supported xtypes
- textfield
- datefield
- datetimefield
- extuxclearabledatefield
- customfieldsearchcombo

