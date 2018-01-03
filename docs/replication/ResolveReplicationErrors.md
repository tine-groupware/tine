
Tine 2.0 Resolve Replication Errors
=================

Version: Caroline 2017.11

Problemlösungen bei Fehlern der Replikation

Allgemeines
=================

replication configuration

    /***** REPLICATION *****/
    'replicationSlave' => array(
        'masterURL' => 'https://master.tine20.example',
        'masterUsername' => 'replicationuser',
        'masterPassword' => '****',
        // error emails are sent to this user
        'errorNotificationList' => array('tine20admin@kvs.pfarrverwaltung.de', 'tine20@metaways.de'),
    ),

run replication:

    $ php tine20.php --method Tinebase.readModifictionLogFromMaster

skip modlog

    $ php tine20.php --method Tinebase.increaseReplicationMasterId

skip multiple (10 in the example) modlogs

    $ php tine20.php --method Tinebase.increaseReplicationMasterId  -- count=10

scheduler default settings: the replication is run on the slave every hour.

herausfinden, auf welchem modlog-stand der client gerade ist

    mysql> select * from tine20_applications where name = 'Tinebase';
    +------------------------------------------+----------+---------+-------+---------+---------------------------------------------------------------------------------------------------+
    | id                                       | name     | status  | order | version | state                                                                                             |
    +------------------------------------------+----------+---------+-------+---------+---------------------------------------------------------------------------------------------------+
    | 6bc1e6588ca6090124e89362cd6734c7528bf952 | Tinebase | enabled |    99 | 11.16   | {"replicationMasterId":"12303","filesystemRootSize":1998529,"filesystemRootRevisionSize":1286501} |
    +------------------------------------------+----------+---------+-------+---------+---------------------------------------------------------------------------------------------------+
    1 row in set (0.00 sec)

"replicationMasterId":"12303" -> this is the id of the latest modlog that has been replicated from master.

to find the current modlog from master:

    mysql> select * from tine20_timemachine_modlog where instance_seq = 12303\G
    *************************** 1. row ***************************
                      id: 9d1f2c2b94575c88c4b55af3d7388e531e5f459a
             instance_id: e98d6f36c477820d69cce83984f4d81c00f8e87d
            instance_seq: 12303
             change_type: created
          application_id: 60a3bf96dc065dee0e223a9b48572b4f6b46f3ae
               record_id: 332b6d0f7b3b52000f25c5e2e43801d679be6969
             record_type: Offertory_Model_OffertoryPlan
          record_backend: Sql
       modification_time: 2018-01-02 15:06:14
    modification_account: 16688d325cc9e719ce084813d31fd0aa795eb8a3
      modified_attribute: NULL
               old_value: NULL
               new_value: {"diff":{"id":"332b6d0f7b3b52000f25c5e2e43801d679be6969","start":"2018-01-05 16:00:00","end":"2018-01-06 15:59:00","offertory_purpose":"Aktion Sternsinger (Drei-K\u00f6nigs-Singen)","initiator":"Di\u00f6zese","dioceses_charge":"50","turn_in_due":"2018-02-06 00:00:00","last_modified_by":null,"last_modified_time":null,"deleted_by":null,"deleted_time":null,"is_deleted":"0","tags":[],"relations":[],"attachments":[],"notes":[]}}
                     seq: 1
                  client: Tinebase_Server_Json - Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36
    1 row in set (0.00 sec)

to find next (failing?) modlog from master

    mysql> select * from tine20_timemachine_modlog where instance_seq > 12303 limit 1\G

to find all master modlogs from a certain period (NOTE: only mods with instance_id are replicated):

    mysql> select record_id,instance_seq,instance_id,change_type,modification_time,record_type from tine20_timemachine_modlog where modification_time > '2018-01-10 16:37' and instance_id is not NULL order by instance_seq;
    +------------------------------------------+--------------+------------------------------------------+-------------+---------------------+--------------------------------+
    | record_id                                | instance_seq | instance_id                              | change_type | modification_time   | record_type                    |
    +------------------------------------------+--------------+------------------------------------------+-------------+---------------------+--------------------------------+
    | 14c6df01a6bf800f985055274ef7947e479f8a40 |        13527 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:40 | Tinebase_Model_Application     |
    | 35325aeea676936daf8868ffba8c82cbd746a551 |        13537 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:40 | Tinebase_Model_Tree_FileObject |
    | 47564e1d1d4c96ce8e353c896cedc75e8574beaa |        13539 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:40 | Tinebase_Model_Tree_Node       |
    | 0b119f9d117e54cee0db4f7b87b95d28fc3702e1 |        13541 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:40 | Tinebase_Model_Tree_FileObject |
    | 804d1743983ed0071b948dffaff32371ad52918d |        13543 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:40 | Tinebase_Model_Tree_Node       |
    | 0b119f9d117e54cee0db4f7b87b95d28fc3702e1 |        13545 | e98d6f36c477820d69cce83984f4d81c00f8e87d | updated     | 2018-01-10 16:37:40 | Tinebase_Model_Tree_FileObject |
    | 67e94389ebfc1ddc800e61f4536e905fede12186 |        13559 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_FileObject |
    | 4d6512bb15b81aaa7f18d71c0f1188fb7e49ca8e |        13561 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_Node       |
    | 67e94389ebfc1ddc800e61f4536e905fede12186 |        13563 | e98d6f36c477820d69cce83984f4d81c00f8e87d | updated     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_FileObject |
    | 7e9ca251356c95b835eb039bc017d39b9a4e74cf |        13577 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_FileObject |
    | 472f9eeeef8f220d0fc790a2a80e4d75adac2cd6 |        13579 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_Node       |
    | 7e9ca251356c95b835eb039bc017d39b9a4e74cf |        13581 | e98d6f36c477820d69cce83984f4d81c00f8e87d | updated     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_FileObject |
    | 87700527f1483fb1e93d45500102b42f93a11598 |        13595 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_FileObject |
    | 0a736e4416175fda6e14161ce823a89e4dec425c |        13597 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_Node       |
    | 87700527f1483fb1e93d45500102b42f93a11598 |        13599 | e98d6f36c477820d69cce83984f4d81c00f8e87d | updated     | 2018-01-10 16:37:41 | Tinebase_Model_Tree_FileObject |
    | dacf69a9e98de222edf07ec0d12a2d36d94513c0 |        13613 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:44 | Tinebase_Model_Tree_FileObject |
    | b8c84339b0cadaf6d31aa2f7f143f623d668d9f1 |        13617 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:44 | Tinebase_Model_Tree_FileObject |
    | 474967cdd843d87650a7e66dae40bd59230ab9ac |        13669 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:45 | Tinebase_Model_Tree_FileObject |
    | 51e8b37c064b08c824e66f300753eb58dda571d0 |        13673 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:45 | Tinebase_Model_Tree_FileObject |
    | dd81ce5579f1dc5a2cbe266b3e36cd790a561822 |        13741 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:46 | Tinebase_Model_Tree_FileObject |
    | b52ff30736a16919ab00731328427c8b1a33a568 |        13745 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:46 | Tinebase_Model_Tree_FileObject |
    | e61c08ba4443f0450d8a67e665c29bc36725a36f |        13781 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:46 | Tinebase_Model_Tree_FileObject |
    | c021267661e12d41d2396722c507a9fb7ad216c4 |        13785 | e98d6f36c477820d69cce83984f4d81c00f8e87d | created     | 2018-01-10 16:37:46 | Tinebase_Model_Tree_FileObject |
    +------------------------------------------+--------------+------------------------------------------+-------------+---------------------+--------------------------------+
    23 rows in set (0.09 sec)


Problem: Role Replication Fail
=================

if the role (rights/members) replication fails, this has to be resolved "by hand".

maybe it is event necessary to create a new admin user/role with --create_admin!

Problem: Data has been deleted on Slave
=================

es kann sein, dass auf dem master daten verändert werden, die auf dem slave
 bereits gelöscht sind. in diesem fall macht man am besten ein undelete auf dem
 slave. falls das nicht geht, muss die änderung geskippt werden.

Problem: Concurrency Conflict
=================

die gleichen felder eines records wurden auf dem master und slave geändert.

lösung: entweder die änderung auf dem slave rückgängig machen oder skip modlog.

Problem: Update/Install Script Fail
=================

update/install skript prüft nicht, ob man auf dem slave ist und legt
 evtl daten doppelt an.

    $setup = new Setup_Update_Abstract(Setup_Backend_Factory::factory());
    if ($setup->isReplicationSlave()) {
        $this->setApplicationVersion('MyApp', '1.2');
        return;
    }

    // DO MASTER UPDATE STUFF

    $this->setApplicationVersion('MyApp', '1.2');


Problem: Slaves laufen in "Duplicate Record" Fehler
=================

Wir hatten einmal den Fall, dass auf den Slaves die Master-Instance-Seq/ID nicht erhöht wurde.

Allerdings haben wir noch nicht verstanden, wieso.

Siehe auch #1299 replication problems fixen (Fileobjects replication) nach MM install
-> https://taiga.metaways.net/project/pschuele-erzbistum-hh/us/1299
