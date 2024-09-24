<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to manage script generation for sieve forwardings
 *
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Forward
{
    /**
     * email addresses
     *
     * @var array
     */
    protected array $_addresses = [];
    
    public function __toString()
    {
        if (count($this->_addresses) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . 'reply to option is null , skip resoling reply-to header');
            return '';
        }
        $result = 'require ["envelope", "copy", "reject", "editheader", "variables"];' . PHP_EOL;
        $this->_addReplyTo($result);
        $this->_addForwarding($result);
        
        return $result;
    }

    /**
     * @param string $result
     * @return void
     */
    protected function _addForwarding(string &$result): void
    {
        foreach ($this->_addresses as $email) {
            if (! preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $email)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Email address invalid: ' . $email);
                continue;
            }
            $result .= 'redirect "' . $email . '";' . PHP_EOL;
        }
    }

    /**
     * @param string $result
     * @return void
     */
    protected function _addReplyTo(string &$result): void
    {
            $result .= 'if address :matches "from" "*" {
    addheader "Reply-To" "${1}";
}';
        // Add "FW:" as a prefix to the subject
        $result .= 'if header :matches "subject" "*" {
            set "original_subject" "${1}";
    deleteheader "Subject";
    addheader "Subject" "FW: ${original_subject}";
}';
        
        $result .= PHP_EOL . PHP_EOL;
    }

    /**
     * add addresses
     *
     * @param   array  $addresses    the addresses
     * @return  Felamimail_Sieve_Forward
     */
    public function setAddresses($addresses)
    {
        $this->_addresses = $addresses;

        return $this;
    }

    /**
     * return values as array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'addresses'             => $this->_addresses,
        ];
    }
}
