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
class Tinebase_WebDav_Plugin_PrincipalSearch extends \Tine20\DAV\ServerPlugin {

    /**
     * Reference to server object
     *
     * @var \Tine20\DAV\Server
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
     * @see \Tine20\DAV\ServerPlugin::getPluginName()
     */
    public function getPluginName() 
    {
        return 'calendarserverPrincipalSearch';
    }
    
    /**
     * (non-PHPdoc)
     * @see \Tine20\DAV\ServerPlugin::getSupportedReportSet()
     */
    public function getSupportedReportSet($uri) 
    {
        return array(
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}calendarserver-principal-search'
        );

    }
    /**
     * Initializes the plugin 
     * 
     * @param \Tine20\DAV\Server $server 
     * @return void
     */
    public function initialize(\Tine20\DAV\Server $server) 
    {
        $this->server = $server;

        $server->xmlNamespaces[\Tine20\CalDAV\Plugin::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[\Tine20\CalDAV\Plugin::NS_CALENDARSERVER] = 'cs';

        #$server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));
        $server->subscribeEvent('report',array($this,'report'));
        
        array_push($server->protectedProperties,
            // CalendarServer extensions
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'
        );
    }
    
    /**
     * beforeGetProperties
     *
     * This method handler is invoked before any after properties for a
     * resource are fetched. This allows us to add in any CalDAV specific
     * properties.
     *
     * @param string $path
     * @param DAV\INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    #public function beforeGetProperties($path, \Tine20\DAV\INode $node, &$requestedProperties, &$returnedProperties) 
    #{
    #    if ($node instanceof \Tine20\DAVACL\IPrincipal) {var_dump($path);
    #        // schedule-outbox-URL property
    #        #'{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'        => 'GROUP',
    #        $property = '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type';
    #        if (in_array($property,$requestedProperties)) {
    #            list($prefix, $nodeId) = Tine20\DAV\URLUtil::splitPath($path);
    #            
    #            unset($requestedProperties[array_search($property, $requestedProperties)]);
    #            $returnedProperties[200][$property] = ($prefix == Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS) ? 'GROUP' : 'INDIVIDUAL';

    #        }
    #    }
    #}
    
    /**
     * This method handles HTTP REPORT requests
     *
     * @param string $reportName
     * @param \DOMNode $dom
     * @return bool
     */
    public function report($reportName, $dom) 
    {
        switch($reportName) {
            case '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}calendarserver-principal-search':
                $this->_principalSearchReport($dom);
                return false;
        }
    }
    
    protected function _principalSearchReport(\DOMDocument $dom) 
    {
        $requestedProperties = array_keys(\Tine20\DAV\XMLUtil::parseProperties($dom->firstChild));
        
        $searchTokens = $dom->firstChild->getElementsByTagName('search-token');

        $searchProperties = array();
        
        if ($searchTokens->length > 0) {
            $searchProperties['{http://calendarserver.org/ns/}search-token'] = $searchTokens->item(0)->nodeValue;
        }
        
        $result = $this->server->getPlugin('acl')->principalSearch($searchProperties, $requestedProperties);

        $prefer = $this->server->getHTTPPRefer();

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary','Brief,Prefer');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($result, $prefer['return-minimal']));
    }
}
