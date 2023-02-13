<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Protocol
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Felamimail IMAP backend
 *
 * @package     Felamimail
 * @subpackage  Protocol
 */
class Felamimail_Protocol_Imap extends Zend_Mail_Protocol_Imap
{
    /**
     * Examine and select have the same response. The common code for both
     * is in this method
     * 
     * - overwritten to get UIDNEXT
     *
     * @param  string $command can be 'EXAMINE' or 'SELECT' and this is used as command
     * @param  string $box which folder to change to or examine
     * @return bool|array false if error, array with returned information
     *                    otherwise (flags, exists, recent, uidvalidity)
     */
    public function examineOrSelect($command = 'EXAMINE', $box = 'INBOX', $params=[])
    {
        $this->sendRequest($command, array_merge(array($this->escapeString($box)), $params), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            if ($tokens[0] == 'FLAGS') {
                array_shift($tokens);
                $result['flags'] = $tokens;
                continue;
            }
            switch ($tokens[1]) {
                case 'EXISTS':
                case 'RECENT':
                    $result[strtolower($tokens[1])] = $tokens[0];
                    break;
                case '[HIGHESTMODSEQ':
                    $result['highestmodseq'] = (int)$tokens[2];
                    break;
                case '[UIDVALIDITY':
                    $result['uidvalidity'] = (int)$tokens[2];
                    break;
                case '[UIDNEXT':
                    $result['uidnext'] = (int)$tokens[2];
                    break;
                case '[UNSEEN':
                    $result['unseen'] = (int)$tokens[2];
                    break;
                default:
                    // ignore
            }
        }

        if ($tokens[0] != 'OK') {
            return false;
        }
        
        return $result;
    }

    /**
     * change folder
     *
     * @param string $box change to this folder
     * @param array $params
     * @return bool|array see examineOrselect()
     * @throws Zend_Mail_Protocol_Exception
     */
    public function select($box = 'INBOX', $params = [])
    {
        return $this->examineOrSelect('SELECT', $box, $params);
    }

    /**
     * get status of a folder (unseen, recent, ...)
     * 
     * @param  string $box which folder to change to or examine
     * @return bool|array false if error, array with returned information
     *                    otherwise (messages, recent, unseen)
     * @throws Zend_Mail_Protocol_Exception
     */
    public function getFolderStatus($box = 'INBOX')
    {
        $command = "STATUS";
        $params = '(MESSAGES RECENT UNSEEN)';
        $this->sendRequest($command, array($this->escapeString($box), $params), $tag);

        $result = array();
        while (!$this->readLine($tokens, $tag)) {
            $result['messages'] = (isset($tokens[2][1])) ? (int)$tokens[2][1] : 0;
            $result['recent']   = (isset($tokens[2][3])) ? (int)$tokens[2][3] : 0;
            $result['unseen']   = (isset($tokens[2][5])) ? (int)$tokens[2][5] : 0;
        }

        if ($tokens[0] != 'OK') {
            return false;
        }
        
        return $result;
    }

    /**
     *
     * @param array $_params Parameters for authentication
     * @param string $_method Sasl method
     * @return array Response from server
     * @throws Exception 
     */
    public function saslAuthenticate($_params, $_method = 'PLAIN')
    {
        switch ($_method)
        {
            case 'PLAIN' :
                /*
                * $_params:
                * authcid = an identity associated with the authentication credentials
                * authzid = an identity to act as 
                * password = password for authcid identity
                */
                $authzid = isset($_params['authzid']) ? $_params['authzid'] : '';
                $authcid = isset($_params['authcid']) ? $_params['authcid'] : '';
                $password = isset($_params['password']) ? $_params['password'] : '';
                $auth = array(base64_encode($authzid.chr(0x00).$authcid.chr(0x00).$password));
                return $this->requestAndResponse("AUTHENTICATE $_method", $auth, true);
            default :
                throw new Exception("Sasl method $_method not implemented!");
        }
    }

     /**
     * Fetch Messages UIDs changed since $modseq
     * 
     * TAG3 UID FETCH 1:* (FLAGS) (CHANGEDSINCE 1)
     * * OK [HIGHESTMODSEQ 21] Highest
     * * 1 FETCH (UID 4 FLAGS (\Seen) MODSEQ (20))
     * * 2 FETCH (UID 5 FLAGS (\Seen) MODSEQ (21))
     * TAG3 OK Fetch completed.
     *
     * @param  integer     $modseq  -  $modSeq to search messages since
     * @return array       list of messages ids, flags changed since last modseq
     * @throws Zend_Mail_Protocol_Exception
     */
    public function fetchIdsChangedSinceModSeq($modseq)
    {
        $params = array('1:* (FLAGS) (CHANGEDSINCE ' . $modseq .')');
        
        $this->sendRequest('UID FETCH', $params, $tag);

        $result = array();
        
        while (!$this->readLine($tokens, $tag)) {
            switch ($tokens[1]) {
                case '[HIGHESTMODSEQ':
                    $result['highestModSeq'] = (int)substr($tokens[2], 0, -1);
                    
                    break;
                
                case 'FETCH':
                    while (key($tokens[2]) !== null) {
                        $result['messages'][$tokens[0]][current($tokens[2])] = next($tokens[2]);
                        next($tokens[2]);
                    }
                    break;
            }
        }
        
        return $result;
     }

    /**
     * append a new message to given folder
     *
     * @param string $folder  name of target folder
     * @param string $message full message content
     * @param array  $flags   flags for new message
     * @param string $date    date for new message
     * @return int|bool returns UID of mail on success
     * @throws Zend_Mail_Protocol_Exception
     */
    public function append($folder, $message, $flags = null, $date = null)
    {
        $tokens = array();
        $tokens[] = $this->escapeString($folder);
        if ($flags !== null) {
            $tokens[] = $this->escapeList($flags);
        }
        if ($date !== null) {
            $tokens[] = $this->escapeString($date);
        }
        $tokens[] = $this->escapeString($message);

        $result = $this->requestAndResponse('APPEND', $tokens);
        if ($result) {
            // TODO it would be better to parse APPENDUID from APPEND response - but we need to teach zend-mail the parsing
            // (or use a different mail lib)
            $select = $this->select($folder);
            // return uidnext if given
            return isset($select['uidnext']) ? $select['uidnext'] - 1 : false;
        } else {
            return false;
        }
    }

    /**
     * TODO: implement selection dialog to subscribe/unsubscribe imap folder
     * subscribe a new folder
     *
     * @param string $folder folder name
     * @return bool
     * @throws Zend_Mail_Protocol_Exception
     */
    public function subscribe($folder): bool
    {
        return $this->requestAndResponse('SUBSCRIBE', [$this->escapeString($folder)]);
    }

    /**
     * TODO: implement selection dialog to subscribe/unsubscribe imap folder
     * unsubscribe a folder
     *
     * @param string $folder folder name
     * @return bool
     * @throws Zend_Mail_Protocol_Exception
     */
    public function unsubscribe($folder): bool
    {
        return $this->requestAndResponse('UNSUBSCRIBE', [$this->escapeString($folder)]);
    }
}
