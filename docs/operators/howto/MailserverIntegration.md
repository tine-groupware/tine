# Mailserver integration

(this might be a little bit outdated - the howto has been transferred from the old wiki, see https://github.com/tine20/tine20/wiki/EN%3AMailserver)

Maintaining email accounts can be very time-consuming task. Creating accounts on the imap server, add the needed email aliases on the smtp server and also disable accounts when a user has left the company.

tine Groupware makes this an easy task. tine Groupware comes with multiple backends to manage different imap and smtp servers. Currently following servers got tested until now:

## IMAP
Cyrus (managed via IMAP)
Dovecot with SQL backend

## SMTP
Postfix with SQL backend
Postfix with LDAP backend
Especially the LDAP backends are very generic. This makes is very likely that they will also work with other IMAP/SMTP ldap enabled servers.

# migration from old database versions

see https://github.com/tine20/tine20/issues/7182

# How to manage Dovecot and Postfix from tine Groupware
Ubuntu comes with a master package to install Dovecot and Postfix in one go. Simply install the postfix-dovecot package by executing following command;

aptitude install dovecot-postfix
This will install all required packages at once. You can find detailed instructions how to configure Postfix here. The same page is available for Dovecot here. The Ubuntu server guide should answer the questions to get a basic mailsystem up and running.

here is a tutorial for the installation of postfix/dovecot/mysql on ubuntu lucid lynx (10.04 LTS): http://library.linode.com/email/postfix/dovecot-mysql-ubuntu-10.04-lucid

Configuring Dovecot with MySQL backend
Dovecot supports multiple backends to store user accounts and authentication data. In our case we choose the MySQL backend. tine Groupware creates the user accounts in 2 MySQL tables, where Dovecot can read them from.

These are the SQL statement needed to create the 2 tables:

https://github.com/tine20/tine20/blob/main/etc/sql/dovecot_tables.sql

here are some required dovecot config files for the mail setup:


/etc/dovecot/dovecot-sql.conf:
```
driver = mysql
connect = host=127.0.0.1 dbname=dovecot user=dbuser password=****
```

# Default password scheme.
```
default_pass_scheme = PLAIN-MD5
```

# passdb with userdb prefetch
```
password_query = SELECT dovecot_users.username AS user,
   password,
   home AS userdb_home,
   uid AS userdb_uid,
   gid AS userdb_gid,
   CONCAT('*:bytes=', CAST(quota_bytes AS CHAR), 'M') AS userdb_quota_rule
   FROM dovecot_users
   WHERE dovecot_users.username='%u'
```

# userdb for deliver
```
user_query = SELECT home, uid, gid,
   CONCAT('*:bytes=', CAST(quota_bytes AS CHAR), 'M') AS userdb_quota_rule
   FROM dovecot_users
   WHERE dovecot_users.username='%u'
```

/etc/dovecot/conf.d/01-mail-stack-delivery.conf

   # Some general options
```
   protocols = imap pop3 sieve
   disable_plaintext_auth = yes
   ssl = yes
   ssl_cert_file = /etc/ssl/certs/ssl-mail.pem
   ssl_key_file = /etc/ssl/private/ssl-mail.key
   ssl_cipher_list = ALL:!LOW:!SSLv2:ALL:!aNULL:!ADH:!eNULL:!EXP:RC4+RSA:+HIGH:+MEDIUM
   mail_location = maildir:~/Maildir
   auth_username_chars = abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890.-_@
   first_valid_uid = 100
```

   # IMAP configuration
```
   protocol imap {
           mail_max_userip_connections = 10
           imap_client_workarounds = outlook-idle delay-newmail
           mail_plugins = quota imap_quota
   }
```

   # POP3 configuration
```
   protocol pop3 {
           mail_max_userip_connections = 10
           pop3_client_workarounds = outlook-no-nuls oe-ns-eoh
           mail_plugins = quota
   }
```

   # LDA configuration
```
   protocol lda {
           postmaster_address = postmaster
           mail_plugins = sieve quota
           quota_full_tempfail = yes
           deliver_log_format = msgid=%m: %$
           rejection_reason = Your message to <%t> was automatically rejected:%n%r
   }
```

   # Plugins configuration
```
   plugin {
       sieve=~/.dovecot.sieve
       sieve_dir=~/sieve
       quota = dict:user::proxy::quotadict
   }
```

```
   dict {
       quotadict = mysql:/etc/dovecot/dovecot-dict-sql.conf
   }
```

/etc/dovecot/auth.d/01-mail-stack-delivery.auth

```
    mechanisms = plain login
    socket listen {
        master {
            # Master socket provides access to userdb information. It's typically
            # used to give Dovecot's local delivery agent access to userdb so it
            # can find mailbox locations.
            path = /var/run/dovecot/auth-master
            mode = 0600
            # Default user/group is the one who started dovecot-auth (root)
            user = deliver
            #group = 
        }
        client {
            path = /var/spool/postfix/private/dovecot-auth
            mode = 0660
            user = postfix
            group = postfix
        }
    }
```
you need to add a new system user "deliver" for this setup and `chown` /var/mail to belong this deliver user.


Configuring Postfix with MySQL backend
table structure for postfix with mysql:

https://github.com/tine20/tine20/blob/main/etc/sql/postfix_tables.sql

here come the main postfix config files:


/etc/postfix/main.cf:

```
   smtpd_banner = $myhostname ESMTP $mail_name (Ubuntu)
   biff = no

   # appending .domain is the MUA's job.
   append_dot_mydomain = no

   readme_directory = no

   # TLS parameters
   smtpd_tls_cert_file = /etc/ssl/certs/ssl-mail.pem
   smtpd_tls_key_file = /etc/ssl/private/ssl-mail.key
   smtpd_use_tls = yes
   smtpd_tls_session_cache_database = btree:${data_directory}/smtpd_scache
   smtp_tls_session_cache_database = btree:${data_directory}/smtp_scache

   # See /usr/share/doc/postfix/TLS_README.gz in the postfix-doc package for
   # information on enabling SSL in the smtp client.

   myhostname = servername
   alias_maps = hash:/etc/aliases
   alias_database = hash:/etc/aliases
   myorigin = /etc/mailname
   mydestination = servername, localhost
   relayhost = 
   mynetworks = 127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128
   mailbox_size_limit = 0
   recipient_delimiter = +
   inet_interfaces = loopback-only
   default_transport = error
   relay_transport = error
   home_mailbox = Maildir/
   smtpd_sasl_auth_enable = yes
   smtpd_sasl_type = dovecot
   smtpd_sasl_path = private/dovecot-auth
   smtpd_sasl_authenticated_header = yes
   smtpd_sasl_security_options = noanonymous
   smtpd_sasl_local_domain = $myhostname
   broken_sasl_auth_clients = yes
   smtpd_recipient_restrictions = reject_unknown_sender_domain, reject_unknown_recipient_domain, reject_unauth_pipelining, permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination
   smtpd_sender_restrictions = reject_unknown_sender_domain
   mailbox_command = /usr/lib/dovecot/deliver -c /etc/dovecot/conf.d/01-mail-stack-delivery.conf -n -m "${EXTENSION}"
   smtp_use_tls = yes
   smtpd_tls_received_header = yes
   smtpd_tls_mandatory_protocols = SSLv3, TLSv1
   smtpd_tls_mandatory_ciphers = medium
   smtpd_tls_auth_only = yes
   tls_random_source = dev:/dev/urandom

   dovecot_destination_recipient_limit = 1

   virtual_transport = dovecot 

   virtual_mailbox_domains = mysql:/etc/postfix/sql/sql-virtual_mailbox_domains.cf
   virtual_mailbox_maps = mysql:/etc/postfix/sql/sql-virtual_mailbox_maps.cf
   virtual_alias_maps = mysql:/etc/postfix/sql/sql-virtual_alias_maps_aliases.cf
```

/etc/postfix/sql/sql-virtual_alias_maps_aliases.cf

```
   user     = dbuser
   password = *****
   hosts    = 127.0.0.1
   dbname   = postfix
   query    = SELECT destination FROM smtp_destinations WHERE source='%s'
```

/etc/postfix/sql/sql-virtual_mailbox_domains.cf

```
   user     = dbuser
   password = *****
   hosts    = 127.0.0.1
   dbname   = postfix
   query    = SELECT DISTINCT 1 FROM smtp_destinations WHERE SUBSTRING_INDEX(source, '@', -1) = '%s';
```

/etc/postfix/sql/sql-virtual_mailbox_maps.cf

```
   user     = dbuser
   password = *****
   hosts    = 127.0.0.1
   dbname   = postfix
   query    = SELECT 1 FROM smtp_users WHERE username='%s' AND forward_only=0
```

and add this lines to the bottom of /etc/postfix/master.cf:

```
   dovecot   unix  -       n       n       -       -       pipe 
     flags=DRhu user=deliver:deliver argv=/usr/lib/dovecot/deliver -c /etc/dovecot/conf.d/01-mail-stack-delivery.conf -f ${sender} -d ${user}@${nexthop} -n -m ${extension}
```

Installing tine Groupware with dovecot/postfix settings (CLI)
install tine Groupware with this CLI command (adjust your mailsettings, initial username / pw and the uid/gid of your "deliver" user (both 999 in this example)):

```
 php setup.php --install -- adminLoginName="admin" adminPassword="***" adminEmailAddress="admin@example.org" acceptedTermsVersion=1000 imap="host:localhost,port:143,useSystemAccount:1,ssl:tls,domain:example.org,backend:dovecot_imap,dovecot_host:localhost,dovecot_dbname:dovecot,dovecot_username:dbuser,dovecot_password:bung4Phi,dovecot_uid:999,dovecot_gid:999,dovecot_home:/var/spool/mail/%d/%n,dovecot_scheme:SHA256" smtp="backend:postfix,hostname:localhost,port:25,ssl:tls,auth:login,primarydomain:example.org,username:notification@example.org,password:****,from:notification@example.org,postfix_host:localhost,postfix_dbname:postfix,postfix_username:dbuser,postfix_password:*****" sieve="hostname:localhost"
```
