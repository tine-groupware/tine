<?php

use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\IPrincipal;

/**
 * CalDAV plugin for expanded-group-member-set
 *
 * NOTE: for expand-property reports some properties seem to be prefixed with 'expanded-':
 * - expanded-group-member-set
 * - expanded-group-membership
 *
 * It's not clear if this is according to the standards, but iCal sends this requests and
 * Sabre can't cope with it yet
 *
 * @copyright  Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author     Cornelius Weiß <c.weiss@metaways.de>
 * @license    http://www.gnu.org/licenses/agpl.html
 */
class Tinebase_WebDav_Plugin_ExpandedPropertiesReport extends \Sabre\DAV\ServerPlugin {

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
        return array();
    }

    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ServerPlugin::getPluginName()
     */
    public function getPluginName() 
    {
        return 'expandPropertiesReport';
    }
    
    /**
     * (non-PHPdoc)
     * @see \Sabre\DAV\ServerPlugin::getSupportedReportSet()
     */
    public function getSupportedReportSet($uri) 
    {
        return array(
            '{DAV:}expand-property',
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

        $server->on('propFind',$this->propFind(...));
    }

    public function propFind(\Sabre\DAV\PropFind $propFind, \Sabre\DAV\INode $node)
    {
        /* Adding principal properties */
        if ($node instanceof IPrincipal) {
            $propFind->handle('{http://calendarserver.org/ns/}expanded-group-member-set', function () use ($node, $propFind) {
                $groupMemberSet = $node->getGroupMemberSet();

                // iCal want's to have the group itself in the response set
                $groupMemberSet[] = $propFind->getPath();

                // have record for group itself
                $groupMemberSet[] = str_replace(
                    Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS,
                    Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS,
                    $propFind->getPath()
                );

                return new Sabre\DAV\Xml\Property\Href($groupMemberSet);
            });
        }
    }
}
