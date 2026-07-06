= Admin =

## Admin.importUser ===

### description ====
Import new users. Accepts the same params as appName.import.

### usage example ====
php tine20.php --method Admin.importUser --username=me -v definition=admin_user_import_csv /path/to/users.csv


## Admin.importGroups ===

### description ====
Import new Groups.

### usage example ====
php tine20.php --method Admin.importGroups --username=me -v definition=admin_group_import_csv /path/to/group.csv


## Admin.createSystemGroupsForAddressbookLists ===

### description ====
create system groups for addressbook lists that don't have a system group.

### usage example ====
php tine20.php --username "tine20admin" --method Admin.createSystemGroupsForAddressbookLists


## Admin.repairGroups ===

### description ====
Add missing lists and checks if list container has been deleted (hides groups if that's the case)

### usage example ====
php tine20.php --username "tine20admin" --method Admin.repairGroups


## Admin.repairUserSambaoptions ===

### description ====
Overwrite Samba options for users. You may set the Samba options in Admin/Frontend/Cli.php

### usage example ====
php tine20.php --username "tine20admin" --method Admin.repairUserSambaoptions

### options ====
--dry


## Admin.shortenLoginnames ===

### description ====
Shorten loginnmes to fit ad samaccountname (20 Chars). And updates Samba options.

### usage example ====
php tine20.php --username "tine20admin" --method Admin.shortenLoginnames
