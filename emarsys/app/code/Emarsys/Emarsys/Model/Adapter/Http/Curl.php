<?php
/**
 * @category   Emarsys
 * @package    Emarsys_Emarsys
 * @copyright  Copyright (c) 2016 Kensium Solution Pvt.Ltd. (http://www.kensiumsolutions.com/)
 */

namespace Emarsys\Emarsys\Model\Adapter\Http;

use Zend_Http_Client_Adapter_Curl;
use Zend_Http_Client;

class Curl extends Zend_Http_Client_Adapter_Curl
{
    /**
     * @param string $method
     * @param \Zend_Uri_Http $url
     * @param string $http_ver
     * @param array $headers
     * @param string $body
     * @return string
     * @throws \Zend_Http_Client_Adapter_Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function write($method, $url, $http_ver = '1.1', $headers = [], $body = '')
    {
        // fixes inability of Varien_Http_Adapter_Curl to PUT
        if ($method == Zend_Http_Client::PUT) {
            $options = [CURLOPT_CUSTOMREQUEST => Zend_Http_Client::PUT, CURLOPT_POSTFIELDS => $body];
            curl_setopt_array($this->_getResource(), $options);
        }
        return parent::write($method, $url, $http_ver, $headers, $body);
        ;
    }
}
