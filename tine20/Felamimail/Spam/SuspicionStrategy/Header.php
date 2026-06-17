<?php
/**
 * spam suspicion strategy header class for the felamimail
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * spam suspicion strategy header class for the felamimail
 *
 * @package     Felamimail
 */
class Felamimail_Spam_SuspicionStrategy_Header implements Felamimail_Spam_SuspicionStrategy_Interface
{
    /**
     * strategy header key
     *
     * @var string
     */
    private $_header;

    /**
     * strategy value key
     *
     * @var string
     */
    private $_value;

    /**
     * construct suspicion strategy header
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options)
    {
        if (!isset($options['header'])) {
            throw new Exception("spam suspicion strategy config 'header' doesn't have content");
        }

        if (!isset($options['value'])) {
            throw new Exception("spam suspicion strategy config 'value' doesn't have content");
        }

        $this->_header = strtolower($options['header']);
        $this->_value = strtolower($options['value']);
    }

    /**
     * @param $message
     * @return bool
     */
    public function apply($message): bool
    {
        $headers = Felamimail_Controller_Message::getInstance()->getMessageHeaders($message, null, true);
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);

        if (!empty($headers) && isset($normalizedHeaders[$this->_header])) {
            return strtolower($normalizedHeaders[$this->_header]) === $this->_value;
        }

        return false;
    }
}
