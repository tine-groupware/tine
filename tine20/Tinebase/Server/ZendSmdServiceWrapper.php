<?php declare(strict_types=1);
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Server_ZendSmdServiceWrapper extends Zend_Json_Server_Smd_Service
{
    protected $apiTimeout;

    public function setApiTimeout(?int $val): self
    {
        $this->apiTimeout = $val;
        return $this;
    }

    public function getApiTimeout(): ?int
    {
        return $this->apiTimeout;
    }

    /**
     * Cast service description to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = parent::toArray();
        $result['apiTimeout'] = $this->apiTimeout;
        return $result;
    }

    public function __sleep(): array
    {
        $toSerialize = [
            '_name',
            '_return',
        ];
        if (!empty($this->_params)) {
            $toSerialize[] = '_params';
        }
        if (null !== $this->_target) {
            $toSerialize[] = '_target';
        }
        if (null !== $this->apiTimeout) {
            $toSerialize[] = 'apiTimeout';
        }
        if (Zend_Json_Server_Smd::ENV_JSONRPC_1 !== $this->_envelope) {
            $toSerialize[] = '_envelope';
        }
        if ('POST' !== $this->_transport) {
            $toSerialize[] = '_transport';
        }

        return $toSerialize;
    }
}
