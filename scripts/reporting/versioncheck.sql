-- by Cornelius Weiss <c.weiss@metaways.de>
--    Philipp Schüle <p.schuele@metaways.de>
--
-- call it like this:
--   $ mysql -h zshared-mysql1 -u versioncheck versioncheck -p***  --verbose \
--     < /www/versioncheck.officespot20.com/stats.sql | mailx -s "Tine 2.0 versioncheck stats" p.schuele@metaways.de tine20@metaway>
--
-- or via cronjob:
--   34 4 1 * * mysql -h zshared-mysql1 -u versioncheck versioncheck -p***  --verbose \
--              < /www/versioncheck.officespot20.com/stats.sql | mailx -s "Tine 2.0 versioncheck stats" "tine20@metaways.de,sales@metaways.de"

-- do some cleanup
UPDATE `versionchecklog` SET `referer` = replace(`referer`, 'index.php', '');
DELETE FROM `versionchecklog` WHERE `referer` LIKE '%Microsoft-Server-ActiveSync%';
DELETE FROM `versionchecklog` WHERE `referer` LIKE '%webdav%';

-- temp table for stats
CREATE TEMPORARY TABLE IF NOT EXISTS hitsperinstall AS(SELECT SUBSTRING(FROM_UNIXTIME(`dtstamp`),1,7) AS `unit`, SUBSTR(`referer`, 1, 100) as `referer`, COUNT(*) as `hits` FROM `versionchecklog` WHERE `referer` NOT LIKE '%tine20.org%' GROUP BY `unit`,`referer` ORDER BY `unit`);

-- num of installs last month
SELECT COUNT(*) as `installations` from `hitsperinstall` WHERE `unit` = SUBSTRING((DATE_SUB(NOW(), INTERVAL 1 MONTH)),1,7);

-- installation list last month
SELECT `referer`, `hits` from `hitsperinstall` WHERE `unit` = SUBSTRING((DATE_SUB(NOW(), INTERVAL 1 MONTH)),1,7) ORDER BY `hits` DESC;
