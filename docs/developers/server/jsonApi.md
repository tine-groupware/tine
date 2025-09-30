# tine JSON API

tine is separated into two layers. The PHP based backend which runs on the webserver and the JavaScript based frontend which runs in the browser on the user's computer.

JSON-RPC
------

These two layers communicate over [http://json-rpc.org/ JSON-RPC].  [http://json-rpc.org/ JSON-RPC] is a lightweight protocol which perfectly suites the needs of webbased applications, as we in fact can transport whole JavaScript arrays and objects. This makes creating the requests and parsing the response on the client side very trivial.
tine speaks JSON-RPC version 2.0.

### JSON-SMD

Another important piece is [http://groups.google.com/group/json-schema/web/service-mapping-description-proposal JSON-SMD]. The Service Mapping Description (SMD) is a JSON representation of services and its parameters provided by a JSON-RPC server. If you are able retrieve the SMD you have a list of all functions available and accessible by your JSON-RPC client.

### Putting it together

As JSON-RPC is very easy to implement, you can find implementations for various languages. While the PHP JSON-RPC client is bundled with tine, it is known that there also exist implementations for Java and Perl. The client bundled with tine also supports JSON-SMD.

### How to retrieve the Service Mapping Description (SMD)

The tine JSON-RPC server delivers the JSON-SMD by sending a request with the method set to NULL and the request-type being application/json-rpc.
Depending on the users rights, you will receive a list of all functions available. If you log in, you should request the SMD again.

### Sending an JSON-RPC request

While the JSON-RPC protocoll is documented very well, you need to sent a special key after login. This key is called jsonKey and must be sent as header X-Tine20-JsonKey with any request after login. You receive the jsonKey in the resonse of a successful login.
Any tine application(tinebase, addressbook, callender,...) has its own namespace for method names. Any methods for the calendar are prefixed whit calendar for example.

If your implementation does not support JSON-SMD you can also simply have a look at the Frontend/Json.php classes an the specific application directories. We are also working on providing a webpage documenting all methods available over JSON-RPC.

To find out what you need to sent, you can also install the Firebug extension in your Firefox browser and have a look at the requests sent by tine.

### A real life PHP example

Based on Zend_Service_Client which adds a small layer on top of Zend_Json_Client we will provide you a small example how to add a new contact and a new lead over JSON-RPC.

The Client can be found in the Zend Framework library, which is part of tine -> https://github.com/tine20/zendframework1/blob/master/library/Zend/Service/Tine20.php

```php
 // url of your tine installation 
 $tine20Url = 'http://tine20/index.php';
 // login name 
 $tine20Loginname = 'loginname';
 // password
 $tine20Password = 'password';
 // id of addressbook container
 $addressbookContainerId = '9';
 // id of CRM container
 $crmContainerId = '14';
 
 // set include path to find all needed classes
 set_include_path('/var/www/tine20' . PATH_SEPARATOR . '/var/www/tine20/library' .   PATH_SEPARATOR . get_include_path());
 
 require_once 'Zend/Loader/Autoloader.php';
 $autoloader = Zend_Loader_Autoloader::getInstance();
 $autoloader->setFallbackAutoloader(true);
 
 // initialize tine service
 $tine20 = new Zend_Service_Tine20($tine20Url);
 
 // login into tine
 $response = $tine20->login($tine20Loginname, $tine20Password);
 var_dump($response);
 
 // call Tinebase.getAllRegistryData directly
 $result = $tine20->call('Tinebase.getAllRegistryData');
 
 // call Tinebase.getAllRegistryData using a Tinebase proxy class
 $tinebase = $tine20->getProxy('Tinebase');
 $result = $tinebase->getAllRegistryData();
 
 // get a proxy class for the Addressbook namespace
 $addressbook = $tine20->getProxy('Addressbook');
 // create a new contact
 $contact = $addressbook->saveContact(array(
   'n_family' => 'Picard',
   'n_given' => 'Jean-Luc',
   'container_id' => $addressbookContainerId
 ));
 
 // get a proxy class for the CRM namespace
 $crm = $tine20->getProxy('Crm');
 // create a new lead in the CRM and assign contact created above as costumer
 $crm->saveLead(array(
 'lead_name' => 'Test lead',
 'leadstate_id' => 1,
 'leadtype_id' => 1,
 'leadsource_id' => 3,
 'start' => '2009-11-13 21:00:00',
 'description' => 'Added via tine Service',
 'container_id' => $crmContainerId,
 'relations' => array(array(
   'type' => 'CUSTOMER',
   'related_record' => $contact
   ))
 ));
 
 // logout from tine 
 $tine20->logout();
```

### Cookies

If you want to send subsequent requests without logging in again each time, you might have to set cookie headers: https://github.com/tine20/tine20/issues/7171

API Timeout
------

You can define the timeout (in seconds) of a JSON API method with `@apiTimeout` in the PHP docblock syntax (example \Sales_Frontend_Json::createPaperSlip):

~~~ php
    /**
     * @apiTimeout 60
     * @param string $model
     * @param string $documentId
     * @return array
     */
    public function createPaperSlip(string $model, string $documentId): array
~~~

Python JSON-RPC Example Script
------

Python script using 'requests', 'json' and 'http.client' to connect to tine and sync Addressbook
contacts from tine to Sipgate:

``` python title="./scripts/api/tine-sipgate-sync.py"
--8<-- "scripts/api/tine-sipgate-sync.py"
```
