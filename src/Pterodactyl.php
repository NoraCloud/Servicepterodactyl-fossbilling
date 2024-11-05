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
use Exception;

class PterodactylAPI {

    private string $api_url;
    private string $api_key;
    private bool $https;

    public function __construct(string $api_url, string $api_key, bool $https = true) {
        $this->api_url = $api_url;
        $this->api_key = $api_key;
        $this->https = $https;
    }

    private function _callAPI(string $method, string $url, array $data = []) {
        $curl = curl_init();
        //TODO use /api in url
        $url = ($this->https ? 'https://' : 'http://') . $this->api_url . $url;
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 

        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if(curl_errno($curl)) {
            $msg = curl_error($curl);
            curl_close($curl);
            throw new Exception('Error connecting to API: ' . $msg, $status);
        }

        curl_close($curl);

        switch ($status) {
            case 200:
            case 201:
                break;
            case 401:
                throw new Exception('Unauthorized API call: ' . $response, $status);
            case 403:
                throw new Exception('Forbidden API call: ' . $response, $status);
            default:
        }

        return [
            'status' => $status,
            'content' => json_decode($response, true),
        ];

    }

    public function getServerList() {
        return $this->_callAPI('GET', '/api/application/servers');
    }

    /**
     * Method to get the eggs list
     * @return array Eggs list
     */
    public function getEggsList() {
        $nests = $this->_callAPI('GET', '/api/application/nests');
        $eggs = [];
        foreach ($nests['content']['data'] as $nest) {
            $nest_id = $nest['attributes']['id'];
            $response = $this->_callAPI('GET', '/api/application/nests/' . $nest_id . '/eggs');
            $eggs = array_merge($eggs, $response['content']['data']);
        }
        return [
            'status' => $nests['status'],
            'content' => $eggs,
        ];
    }

    /**
     * Method to get the users list
     * @return array Users list
     */
    public function getUsersList() {
        return $this->_callAPI('GET', '/api/application/users');
    }

    /**
     * Method to get node details
     * @param int $node_id Node ID
     * @return array Node details
     */
    public function getNodeDetails(int $node_id) {
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
            throw new Exception('FQDN does not resolve to IP', 400);
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
                       throw new Exception('Error connecting to node: ' . $msg, $status);
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
        throw new Exception('Node not found', 404);
    }

    /**
     * Get server count
     * @return int Server count
     */
    public function getServerCount(): int {
        $response = $this->_callAPI('GET', '/api/application/servers');
        return $response['content']['meta']['pagination']['total'];
    }

        /**
     * Find user by email
     * @param string $email User email
     * @return array User details
     */
    public function findUserByEmail(string $email) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email', 400);
        }
        $response = $this->_callAPI('GET', '/api/application/users?filter[email]=' . $email);
        if(empty($response['content']['data']) || $response['content']['data'][0]['attributes']['email'] !== $email) {
            throw new Exception('User not found', 404);
        }
        return $response;
    }

    /**
     * Create user account
     * @param string $email User email
     * @param string $username User username
     * @param string $first_name User first name
     * @param string $last_name User last name
     * @return array User details
     */
    public function createUser(string $email, string $username, string $first_name, string $last_name) {
        foreach ([$email, $username, $first_name, $last_name] as $value) {
            if (empty($value)) {
                throw new Exception('All fields are required', 400);
            }
        }
        $data = [
            'email' => $email,
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'password' => bin2hex(random_bytes(16)),
        ];
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email', 400);
        }
        $response = $this->_callAPI('POST', '/api/application/users', $data);
        if(!str_contains(json_encode($response['content']), 'uuid')) {
            if (str_contains(json_encode($response['content']), 'unique')) {
                if(($response['content']['errors'][0]['meta']['source_field'] ?? "") === 'email') {
                    throw new Exception('Email already exists', 409);
                }
                if(($response['content']['errors'][0]['meta']['source_field'] ?? "") === 'username') {
                    $data['username'].= bin2hex(random_bytes(4));
                }
            }
            if (str_contains(json_encode($response['content']), 'required')) {
                $data['username'] = empty($username) ? preg_replace('/[^a-zA-Z0-9]/', '', $email) : $username;
                $data['first_name'] = empty($first_name) ? $username : $first_name;
                $data['last_name'] = empty($last_name) ? $username : $last_name;
            }
            if(str_contains(json_encode($response['content']), 'p_username')) {
                $data['username'] = preg_replace('/[^a-zA-Z0-9]/', '', $username);
            }
        $response = $this->_callAPI('POST', '/api/application/users', $data);
        if(!str_contains(json_encode($response['content']), 'uuid')) {
            throw new Exception('User creation failed', 500);
        }
     
        }

        return $response;
    
    }


    /**
     * Get the first free allocation ID
     * @param string $fqdn Node FQDN
     * @return int Allocation ID
     */
    public function getFirstFreeAllocationId(string $fqdn) {
        $node_id = $this->_getNodeId($fqdn);
        $allocations = $this->_callAPI('GET', '/api/application/nodes/' . $node_id . '/allocations')['content'];
        if (empty($allocations) || !isset($allocations['data'])) {
            throw new Exception('No allocations found', 404);
        }
        foreach ($allocations['data'] as $allocation) {
            if(!$allocation['assigned']) {
                return $allocation['id'];
            }
        }
    }

    /**
     * Generate server name
     * @param string $username User username
     * @return string Server name
     */
    private function _generateServerName(string $username) {
        return $username . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Create pterodactyl server
     * @param string $email User email
     * @param string $username User username
     * @param string $first_name User first name
     * @param string $last_name User last name
     * @param string $egg_id Egg ID
     * @param string $docker_image Docker image
     * @param string $startup Startup command
     * @param int $memory Memory limit
     * @param int $swap Swap limit
     * @param int $disk Disk limit
     * @param int $io IO limit
     * @param int $cpu CPU limit
     * @param int $databases Database limit
     * @param int $allocations Allocation limit
     * @param int $backups Backup limit
     * @param int $node_id Node ID
     * @return int Server ID
     */
     public function createServer(string $email, string $username, string $first_name, string $last_name, string $egg_id, string $docker_image, string $startup, int $memory, int $swap, int $disk, int $io, int $cpu, int $databases, int $allocations, int $backups, int $node_id)
    {
        foreach ([$email, $username, $first_name, $last_name, $egg_id, $docker_image, $startup, $memory, $swap, $disk, $io, $cpu, $databases, $allocations, $backups, $node_id] as $value) {
            if (empty($value)) {
                throw new Exception('All fields are required', 400);
            }
        }
        $user_id = $this->findUserByEmail($email);
        if (empty($user_id)) {
            $user_id = $this->createUser($email, $username, $first_name, $last_name);
        }

        $serverData = [
            'json' => [
                "name" => $this->_generateServerName($username),
                "user" => $user_id,
                "egg" => $egg_id,
                "docker_image" => $docker_image,
                "startup" => $startup,
                "limits" => [
                    "memory" => $memory,
                    "swap" => $swap,
                    "disk" => $disk,
                    "io" => $io,
                    "cpu" => $cpu,
                ],
                "feature_limits" => [
                    "databases" => $databases,
                    "allocations" => $allocations,
                    "backups" => $backups,
                ],
                "allocation" => [
                    //TODO definir quelle node sera utilisée
                    "default" => $this->getFirstFreeAllocationId($node_id),
                ],
            ]
        ];

        $response = $this->_callAPI('POST', '/api/application/servers', $serverData);
        if (!str_contains($response['content'], 'uuid')) {
            throw new Exception('Server creation failed', 500);
        }

        return $response['content']['id'];
     }
       


}
