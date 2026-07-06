# All Applications CLI

This document describes the usage of the tine Groupware CLI (`tine20.php`).

## appName.updateImportExportDefinition

### description ====
Used to update import/export definitions in the database by reading the configuration from a file.

### usage example
php tine20.php --username tine20admin --method Addressbook.updateImportExportDefinition importdefinition.xml

### params
(filenames)


## import 
Several apps support imports via appName.import. Available plugins for the app could be found in the appName/Import directory. In the plugin files you also find an $_options array with a list of possible options.

### usage examples
php tine20.php --method=Calendar.import --username=me -v plugin=Calendar_Import_Ical container_id=362 /path/to/Feiertage_DE.ics

php tine20.php --method=Addressbook.import --username=me -v definition=adb_tine_import_csv /path/to/contacts.csv

## setContainerGrants
This CLI function allows to set the grants of containers. You have to know the id of the container and the account/group or you need to supply a filter string for container names (operator: contains).

### usage examples
php tine20.php --method=Calendar.setContainerGrants containerId=3339 accountId=15 accountType=group grants=readGrant
php tine20.php --username admin --method=Timetracker.setContainerGrants namefilter="ta title" accountId=3039,3038 accountType=group grants=book_own,manage_billable overwrite=1

### params
containerId / accountId / accountType / grants / overwrite / namefilter

