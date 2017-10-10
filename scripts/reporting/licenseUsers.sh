#!/bin/bash

# TODO add customer mail
EMAIL="p.schuele@metaways.de sales@metaways.de"
CUSTOMER="CUSTOMER"
ACTIVE_SQL="select count(id) as active from tine20_accounts where status = 'enabled' and last_login > DATE_SUB(CURDATE(),INTERVAL 90 DAY)"
SHADOW_SQL="select count(id) as shadow from tine20_accounts where status = 'enabled' and (last_login < DATE_SUB(CURDATE(),INTERVAL 90 DAY) or last_login is null)"

# fetch data and write to file
echo -e "Tine 2.0 Business Edition Benutzer Report\n" > report.txt
echo -e "Datum: $(date)\n" >> report.txt
echo -e "Anzahl lizenzpflichtiger Tine 2.0 Benutzer: " >> report.txt
mysql tine20 -e "select greatest(active,shadow) as license_user from ($ACTIVE_SQL)act_select, ($SHADOW_SQL)sha_select;" >> report.txt

echo -e "Anzahl Tine 2.0 Benutzer (aktiviert):" >> report.txt
mysql tine20 -e "$ACTIVE_SQL;" >> report.txt

echo -e "Anzahl Schattenbenutzer (Lizenzoption):" >> report.txt
mysql tine20 -e "$SHADOW_SQL;" >> report.txt

echo -e "\nAls Lizenzoption zu einem Tine 2.0 Benutzer kann die Anzahl der Schattenbenutzer nicht größer sein als die Anzahl der lizenzpflichtigen Tine 2.0 Benutzer.\n"  >> report.txt

# send report file via mail
cat report.txt | grep -v license_user | grep -v active | grep -v shadow | mailx -s "$CUSTOMER: Tine 2.0 Business Edition users" $EMAIL
#cat report.txt | grep -v license_user | grep -v active | grep -v shadow

#  delete report file
rm report.txt
