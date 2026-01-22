Timemachine Modification Log HowTo
=================

Version: Liva 2025.11

# Introduction

All changes to records are tracked in the table timemachine_modlog. This table is also used for the replication and
undo functionality (TODO add links to those pages).

# History of a record

To fetch the history of a record, you can use the following SQL command (NOTE: it is important to use the keys
 application_id and record_type here - otherwise the query will run very long...):

~~~sql
select * from tine20_timemachine_modlog where
    application_id in (select id from tine20_applications where name = 'Addressbook')
    and record_id = 'c815b06ecbdfd2333b754465d7eba00cbf343a37'
    and record_type = 'Addressbook_Model_Contact'
    order by modification_time;
~~~
