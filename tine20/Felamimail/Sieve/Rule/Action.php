<?php

/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to store Sieve rule action and to generate Sieve code for action
 * 
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_Rule_Action
{
    const DISCARD   = 'discard';
    const FILEINTO  = 'fileinto';
    const KEEP      = 'keep';
    const REJECT    = 'reject';
    const REDIRECT  = 'redirect';
    const VACATION  = 'vacation';

    /**
     * type of action
     * 
     * @var string
     */
    protected $_type;
    
    /**
     * argument for action
     * 
     * @var string|array
     */
    protected $_argument;

    protected ?Felamimail_Model_Account $_account = null;

    /**
     * set type of action
     * 
     * @param   string  $type   type of action
     * @return  Felamimail_Sieve_Rule_Action
     */
    public function setType($type)
    {
        if(!defined('self::' . strtoupper($type))) {
            throw new InvalidArgumentException('invalid type: ' . $type);
        }
        $this->_type = $type;
        
        return $this;
    }

    /**
     * @param Felamimail_Model_Account $account
     * @return Felamimail_Sieve_Rule_Action
     */
    public function setAccount(Felamimail_Model_Account $account): Felamimail_Sieve_Rule_Action
    {
        $this->_account = $account;
        return $this;
    }

    /**
     * set argument for action
     * 
     * @param   string  $argument   argument
     * @return  Felamimail_Sieve_Rule_Action
     */
    public function setArgument($argument)
    {
        $this->_argument = $argument;
        
        return $this;
    }
    
    /**
     * return the Sieve code for this action
     * 
     * @return string
     */
    public function __toString(): string
    {
        switch ($this->_type) {
            case self::DISCARD:
                return "    $this->_type;";

            case self::REDIRECT:
                if (! is_array($this->_argument)) {
                    $this->_argument = [
                        'emails' => $this->_argument
                    ];
                }
                if (isset($this->_argument['copy']) && $this->_argument['copy'] == 1) {
                    $argument = $this->_quoteString($this->_argument['emails']);
                    return "    $this->_type :copy $argument;";
                } else {
                    $argument = $this->_quoteString($this->_argument['emails']);
                    return "    $this->_type $argument;";
                }

            case self::VACATION:
                $email = $this->_account ? $this->_account->email : Tinebase_Core::getUser()->accountEmailAddress;
                $vacation = new Felamimail_Model_Sieve_Vacation([
                    'addresses' => [$email],
                    'from' => $email,
                    'days' => 0,
                    'reason' => $this->_argument,
                    // TODO add signature?
                ]);
                return (string) $vacation->getFSV();

            default:
                $argument = $this->_quoteString($this->_argument);
                return "    $this->_type $argument;";
        }
    }
    
    /**
     * quote string for usage in Sieve script 
     * 
     * @param   string  $string     the string to quote
     * @return string
     * 
     * @todo generalize this
     */
    protected function _quoteString($string): string
    {
        if (is_array($string)) {
            $string = array_map(array($this, '_quoteString'), $string);
            return '[' . implode(',', $string) . ']';
        } else if ($string === null) {
            return '';
        } else {
            return '"' . str_replace('"', '\"', $string) . '"';
        }
    }
    
    /**
     * return values as array
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            'type'            => $this->_type,
            'argument'        => $this->_argument,
        );
    }
}
