<?php

/**
 * Pterodactyl API class handler
 *
 * @copyright NoraCloud 2024 (https://www.noracloud.fr)
 * @license   Apache-2.0
 *
 * Copyright 2024 NoraCloud
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 */

namespace Box\Mod\Servicepterodactyl;

use FOSSBilling\InformationException;

class PterodactylAPI {

    //create diffent methods for example get server list
    //
    // use a generic method to call the API

    private string $api_url;
    private string $api_key;

    public function __construct(string $api_url, string $api_key) {
        $this->api_url = $api_url;
        $this->api_key = $api_key;
    }

    private function _callAPI(string $method, string $url, array $data = []) {
        $curl = curl_init();
        $url = 'https://' . $this->api_url . $url;
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if(curl_errno($curl)) {
            $msg = curl_error($curl);
            curl_close($curl);
            throw new InformationException('API call failed: ' . $msg, [], '500');
        }

        curl_close($curl);

        switch ($status) {
            case 200:
            case 201:
                break;
            case 401:
                throw new InformationException('API call failed: Unauthorized', [], '401');
            default:
                throw new InformationException('API call failed: ' . $response, [], '500');
        }

        return [
            'status' => $status,
            'content' => json_decode($response, true),
        ];

    }


    public function getServerList() {
        return $this->_callAPI('GET', '/api/application/servers');
    }

    //get node list ??
    //
    //get panel detail
    //get ndode dertail

    /**
     * Method to get node details
     * @param int $node_id Node ID
     * @return array Node details
     */
    public function getNodeDetails(int $node_id) {
        throw new InformationException($node_id, [], '500');
        $response = $this->_callAPI('GET', '/api/application/nodes/' . $node_id);
        return $response['content']['attributes'];
    }

    /**
     * Static method to test node connection
     * @param string $fqdn Node FQDN
     * @param string $ip Node IP
     * @param int $port Node port
     * @return bool Node connection status
     */
    public static function testNodeConnection(string $fqdn, string $ip, int $port = 8080) {
        
        if (gethostbyname($fqdn) !== $ip) {
            throw new InformationException('FQDN does not resolve to IP', [], '500');
        }

        $curl = curl_init();
        $url = 'https://' . $fqdn . ':' . $port . '/download/file';
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if(curl_errno($curl)) {
            $msg = curl_error($curl);
            curl_close($curl);
            throw new InformationException('Error connecting to node: ' . $msg, [], '500');
        }

        curl_close($curl);

        return $status === 401 && json_decode($response, true)['request_id'] !== null;

    }


    /**
     * Get node ID
     * @param string $fqdn Node FQDN
     * @return int Node ID
     */
    private function _getNodeId(string $fqdn) {
        $response = $this->_callAPI('GET', '/api/application/nodes');
        $nodes = $response['content']['data'];
        foreach ($nodes as $node) {
            if ($node['attributes']['fqdn'] === $fqdn) {
                return $node['attributes']['id'];
            }
        }
        throw new InformationException('Node not found', [], '500');
    }

    /**
     * Get server count
     * @return int Server count
     */
    public function getServerCount() {
        $response = $this->_callAPI('GET', '/api/application/servers');
        return $response['content']['meta']['pagination']['total'];
    }

}
