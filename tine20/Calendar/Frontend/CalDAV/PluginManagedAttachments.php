<?php

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * CalDAV plugin for draft-daboo-caldav-attachments-03
 * 
 * see: http://tools.ietf.org/html/draft-daboo-caldav-attachments-03
 * 
 * NOTE: At the moment Apple's iCal clients seem to support only a small subset of the spec:
 * - deleting is done by PUT and not via managed-remove
 * - client does not update files
 * - client can not cope with recurring exceptions. It always acts on the whole serices and all exceptions
 * 
 * @TODO
 * evaluate "return=representation" header
 * add attachments via PUT with managed ID
 
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2014-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Cornelius Weiss <c.weiss@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Calendar_Frontend_CalDAV_PluginManagedAttachments extends \Sabre\DAV\ServerPlugin 
{
    /**
     * Reference to server object
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() 
    {
        return array('calendar-managed-attachments');
    }

    /**
     * Returns a plugin name.
     * 
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin 
     * 
     * @return string 
     */
    public function getPluginName() 
    {
        return 'calendarManagedAttachments';
    }

    /**
     * Initializes the plugin 
     * 
     * @param \Sabre\DAV\Server $server 
     * @return void
     */
    public function initialize(\Sabre\DAV\Server $server) 
    {
        $this->server = $server;
        $server->on('method:POST', [$this, 'httpPOSTHandler']);
        $server->on('propFind', [$this, 'propFind']);
        $server->xml->namespaceMap[\Sabre\CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';
        
    }

    public function propFind(\Sabre\DAV\PropFind $propFind, \Sabre\DAV\INode $node)
    {
        if ($node instanceof \Sabre\DAVACL\IPrincipal) {
            // dropbox-home-URL property
            $propFind->handle('{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}dropbox-home-URL', function() use($node) {
                $principalId = $node->getName();
                return new \Sabre\DAV\Xml\Property\Href(\Sabre\CalDAV\Plugin::CALENDAR_ROOT . '/' . $principalId . '/dropbox');
            });
        }
    }
    
    /**
     * Handles POST requests
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function httpPOSTHandler(RequestInterface $request, ResponseInterface $response)
    {
        $getVars = $request->getQueryParameters();
        
        if (!isset($getVars['action']) || !in_array($getVars['action'], 
                array('attachment-add', 'attachment-update', 'attachment-remove'))) {
            return;
        }
        
        try {
            $node = $this->server->tree->getNodeForPath($request->getPath());
        } catch (\Sabre\DAV\Exception\NotFound $e) {
            // We're simply stopping when the file isn't found to not interfere
            // with other plugins.
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ .
                " did not find node -> stopping");
            }
            return;
        }
        
        if (!$node instanceof Calendar_Frontend_WebDAV_Event) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . 
                    " node is no event -> stopping ");
            }
            return;
        }
        
        $name = 'NO NAME';
        $disposition = $this->server->httpRequest->getHeader('Content-Disposition');
        $contentType = $this->server->httpRequest->getHeader('Content-Type');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
             Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ .
            " disposition/contentType: " . $disposition . ' / '. $contentType);
        
        $managedId = isset($getVars['managed-id']) ? $getVars['managed-id'] : NULL;
        $rid = $this->getRecurranceIds($getVars);
        list($contentType) = explode(';', (string)$contentType);
        if (preg_match("/filename\*=utf-8''(.*)/", $disposition, $matches)) {
            // handle utf-8 dispositions (like this: filename=\"Reservierungsbesta?tigung _ OTTER.txt\";filename*=utf-8''Reservierungsbesta%CC%88tigung%20_%20OTTER.txt)
            $name = $matches[1];
        } else if (preg_match('/filename=(.*)[ ;]{0,1}/', $disposition, $matches)) {
            $name = $matches[1];
        }
        $name = trim($name, " \t\n\r\0\x0B\"'");

        if (is_resource($this->server->httpRequest->getBody())) {
            $inputStream = $this->server->httpRequest->getBody();
        } else {
            $inputStream = fopen('php://temp','r+');
            if (is_string($this->server->httpRequest->getBody())) {
                fwrite($inputStream, $this->server->httpRequest->getBody());
            } elseif (is_callable($this->server->httpRequest->getBody())) {
                fwrite($inputStream, call_user_func($this->server->httpRequest->getBody()));
            }
        }
        rewind($inputStream);

        list ($attachmentId) = Tinebase_FileSystem::getInstance()->createFileBlob($inputStream, avscan: false);
        $hashPath = Tinebase_FileSystem::getInstance()->getRealPathForHash($attachmentId);
        
        switch ($getVars['action']) {
            case 'attachment-add':
                
                $attachment = new Tinebase_Model_Tree_Node(array(
                    'name'         => rawurldecode($name),
                    'type'         => Tinebase_Model_Tree_FileObject::TYPE_FILE,
                    'contenttype'  => $contentType,
                    'hash'         => $attachmentId,
                ), true);
                
                $this->_iterateByRid($node->getRecord(), $rid, function($event) use ($name, $attachment) {
                    $existingAttachment = $event->attachments->filter('name', $name)->getFirstRecord();
                    if ($existingAttachment) {
                        // yes, ... iCal does this :-(
                        $existingAttachment->hash = $attachment->hash;
                    }
                    
                    else {
                        $event->attachments->addRecord(clone $attachment);
                    }
                });
                
                $node->update($node->getRecord());
                
                break;
                
            case 'attachment-update':
                $eventsToUpdate = array();
                // NOTE: iterate base & all exceptions @see 3.5.2c of spec
                $this->_iterateByRid($node->getRecord(), NULL, function($event) use ($managedId, $attachmentId, &$eventsToUpdate) {
                    $attachmentToUpdate = $event->attachments->filter('hash', $managedId)->getFirstRecord();
                    if ($attachmentToUpdate) {
                        $eventsToUpdate[] = $event;
                        $attachmentToUpdate->hash = $attachmentId;
                    }
                });
                
                if (! $eventsToUpdate) {
                    throw new \Sabre\DAV\Exception\PreconditionFailed("no attachment with id $managedId found");
                }
                
                $node->update($node->getRecord());
                break;
                
            case 'attachment-remove':
                $eventsToUpdate = array();
                $this->_iterateByRid($node->getRecord(), $rid, function($event) use ($managedId, &$eventsToUpdate) {
                    $attachmentToDelete = $event->attachments->filter('hash', $managedId)->getFirstRecord();
                    if ($attachmentToDelete) {
                        $eventsToUpdate[] = $event;
                        $event->attachments->removeRecord($attachmentToDelete);
                    }
                });
                
                if (! $eventsToUpdate) {
                    throw new \Sabre\DAV\Exception\PreconditionFailed("no attachment with id $managedId found");
                }
                    
                $node->update($node->getRecord());
                break;
        }

        if (Tinebase_FileSystem_AVScan_Factory::MODE_OFF !== Tinebase_Config::getInstance()
                ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_MODE}) {
            $fileSize = filesize($hashPath);
            $queueSize = Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM)->{Tinebase_Config::FILESYSTEM_AVSCAN_QUEUE_FSIZE};
            if ($fileSize && $fileSize > $queueSize) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(fn() => Tinebase_ActionQueue::getInstance()->queueAction('Tinebase.avScanHashFile', $hashPath));
            } else {
                Tinebase_Controller::getInstance()->avScanHashFile($hashPath);
            }
        }
        
//         @TODO respect Prefer header
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar; charset="utf-8"');
        $this->server->httpResponse->setHeader('Content-Length', $node->getSize());
        $this->server->httpResponse->setHeader('ETag',           $node->getETag());
        if ($getVars['action'] != 'attachment-remove') {
            $this->server->httpResponse->setHeader('Cal-Managed-ID', $attachmentId);
        }
        
        // only at create!
        $this->server->httpResponse->setStatus(201);
        $this->server->httpResponse->setBody($node->get());
        
        return false;

    }
    
    /**
     * calls method with each event matching given rid
     * 
     * breaks if method returns false
     * 
     * @param  Calendar_Model_Event $event
     * @param  array $rid
     * @param  callable $method
     * @return Tinebase_Record_RecordSet affectedEvents
     */
    protected function _iterateByRid($event, $rid, $method)
    {
        $affectedEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        if (! $rid || in_array('M', $rid)) {
            $affectedEvents->addRecord($event);
        }
        
        if ($event->exdate instanceof Tinebase_Record_RecordSet) {
            foreach($event->exdate as $exception) {
                if (! $rid /*|| $exception->recurid ...*/) {
                    $affectedEvents->addRecord($exception);
                }
            }
        }
        foreach ($affectedEvents as $record) {
            if ($method($record) === false) {
                break;
            }
        }
        
        return $affectedEvents;
    }
    
    /**
     * returns recurrance ids
     * 
     * NOTE: 
     *  no rid means base & all exceptions
     *  M means base 
     *  specific dates point to the corresponding exceptions of course
     *  
     * @return array
     */
    public function getRecurranceIds($getVars)
    {
        $recurids = array();
        
        if (isset($getVars['rid'])) {
            foreach ( explode(',', $getVars['rid']) as $recurid) {
                if ($recurid) {
                    $recurids[] = strtoupper($recurid);
                }
            }
        }
        
        return $recurids;
    }
    
}
