<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Auth Redirect exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Auth_Redirect extends Tinebase_Exception_SystemGeneric
{
    protected $_title = 'Auth requires redirect';

    public string $_method = 'GET';
    public string $_url = '';
    public ?string $_postFormHTML = null;
    public ?array $_postData = null;


    public function __construct($_message = null, $_code = 650)
    {
        parent::__construct($_message, $_code);
    }

    public function setMethodPOST(): self
    {
        $this->_method = 'POST';
        return $this;
    }

    public function setPostData(array $data): self
    {
        $this->_postData = $data;
        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->_url = $url;
        return $this;
    }

    public function toArray()
    {
        $result = parent::toArray();

        $result['method'] = $this->_method;
        $result['url'] = $this->_url;
        if (null !== $this->_postData) {
            $result['postFormHTML'] = \Tinebase_Helper::createFormHTML($this->_url, $this->_postData);
        }

        return $result;
    }
}