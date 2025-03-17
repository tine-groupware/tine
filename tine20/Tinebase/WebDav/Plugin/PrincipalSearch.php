<?php
/**
 * CalDAV plugin for calendar-auto-schedule
 * 
 * This plugin provides functionality added by RFC6638
 * It takes care of additional properties and features
 * 
 * see: http://tools.ietf.org/html/rfc6638
 *
 * @package    Sabre
 * @subpackage CalDAV
 * @copyright  Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @license    http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Tinebase_WebDav_Plugin_PrincipalSearch extends \Sabre\DAV\ServerPlugin {

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
        return array('calendarserver-principal-search');
    }

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ServerPlugin::getPluginName()
     */
    public function getPluginName() 
    {
        return 'calendarserverPrincipalSearch';
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ServerPlugin::getSupportedReportSet()
     */
    public function getSupportedReportSet($uri) 
    {
        return array(
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}calendarserver-principal-search'
        );

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

        $server->xml->namespaceMap[\Sabre\CalDAV\Plugin::NS_CALDAV] = 'cal';
        $server->xml->namespaceMap[\Sabre\CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';

        $server->on('report',$this->report(...));
        
        array_push($server->protectedProperties,
            // CalendarServer extensions
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'
        );
    }

    public function report($reportName, $data)
    {
        switch($reportName) {
            case '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}calendarserver-principal-search':
                $this->_principalSearchReport($data);
                return false;
        }
    }
    
    protected function _principalSearchReport(array $searchData)
    {
        $requestedProperties = [];
        $searchToken = null;

        foreach ($searchData as $elem) {
            switch ($elem['name'] ?? '') {
                case '{http://calendarserver.org/ns/}search-token':
                    $searchToken = $elem['value'] ?? null;
                    break;
                //case '{http://calendarserver.org/ns/}limit': break;
                case '{DAV:}prop':
                    foreach ($elem['value'] ?? [] as $subElem) {
                        if ($subElem['name'] ?? false) {
                            $requestedProperties[] = $subElem['name'];
                        }
                    }
                    break;
            }
        }
        
        if ($searchToken) {
            $searchToken = ['{http://calendarserver.org/ns/}search-token' => $searchToken];
        }

        /** @var \Sabre\DAVACL\Plugin $aclPlugin */
        $aclPlugin = $this->server->getPlugin('acl');
        $result = $aclPlugin->principalSearch($searchToken, $requestedProperties);

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return'] === 'minimal'));
    }
}
