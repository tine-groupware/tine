<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold mail account constants
 * 
 * @package   Tinebase
 * @subpackage    EmailUser
 */
abstract class Tinebase_EmailUser_Model_Account extends Tinebase_Record_NewAbstract
{
    /**
     * secure connection setting for no secure connection
     *
     */
    public const SECURE_NONE = 'none';

    /**
     * secure connection setting for tls
     *
     */
    public const SECURE_TLS = 'tls';

    /**
     * secure connection setting for ssl
     *
     */
    public const SECURE_SSL = 'ssl';

    /**
     * adb list account
     */
    public const TYPE_ADB_LIST = 'adblist';

    /**
     * shared account
     */
    public const TYPE_SHARED_INTERNAL = 'shared';

    /**
     * shared account (external))
     */
    public const TYPE_SHARED_EXTERNAL = 'sharedExternal';

    /**
     * system account
     */
    public const TYPE_SYSTEM = 'system';
    
    /**
     * user defined account
     */
    public const TYPE_USER_EXTERNAL = 'user';

    /**
     * user defined account on internal mail system
     */
    public const TYPE_USER_INTERNAL = 'userInternal';

    /**
     * display format: plain
     *
     */
    public const DISPLAY_PLAIN = 'plain';
    
    /**
     * display format: html
     *
     */
    public const DISPLAY_HTML = 'html';
    
    /**
     * signature position above quote
     *
     */
    public const SIGNATURE_ABOVE_QUOTE = 'above';
    
    /**
     * signature position above quote
     *
     */
    public const SIGNATURE_BELOW_QUOTE = 'below';
    
    /**
     * display format: content type
     *
     * -> depending on content_type => text/plain show as plain text
     */
    public const DISPLAY_CONTENT_TYPE = 'content_type';
}
