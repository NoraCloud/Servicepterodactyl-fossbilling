<?php

/**
 * Pterodactyl module for FOSSBilling
 *
 * @copyright NoraCloud 2024 (https://www.noracloud.fr)
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license   Apache-2.0
 *
 * Copyright FOSSBilling 2022
 * This software may contain code previously used in the BoxBilling project.
 * Copyright BoxBilling, Inc 2011-2021
 *
 * This source file is subject to the Apache-2.0 License that is bundled
 * with this source code in the file LICENSE
 */

/**
 * Pterodactyl module Admin API.
 *
 * API can be access only by admins
 */

namespace Box\Mod\Servicepterodactyl\Api;

use Box\Mod\Servicepterodactyl\PterodactylAPI as PterodactylAPI;

require(__DIR__ . DIRECTORY_SEPARATOR . '../Pterodactyl.php');

class Admin extends \Api_Abstract
{
    /**
     * Return list of example objects.
     *
     * @return string[]
     */
    public function get_something($data): array
    {
        $result = [
            'apple',
            'google',
            'facebook',
        ];

        if (isset($data['microsoft'])) {
            $result[] = 'microsoft';
        }

        return $result;
    }

    /**
     * An example that checks if the staff member has permission to the `do_something` permission key.
     * 
     * @return bool 
     */
    public function can_do_something($data)
    {
        // First, get an instance of the staff module
        $staff_service = $this->di['mod_service']('Staff');

        /* Next, we use the staff module to check if the current staff member has permission.
         * We pass `null` to the first parameter to tell it to check against current staff member
         * The second parameter `example` is the name of the module
         * The third parameter is the name of the permission key we are checking (`do_something`)
         */
        if (!$staff_service->hasPermission(null, 'example', 'do_something')) {
            throw new \FOSSBilling\InformationException('You do not have permission to perform this action', [], 403);
        }

        return true;
    }

    /**
     * Method to list all pterodactyl nodes
     * @return array
     */
    public function node_list(): array
    {
        $nodes = $this->di['db']->find('service_pterodactyl_node');
        $result = [];
        foreach ($nodes as $node) {
            $result[] = [
                'id' => $node->id,
                'name' => $node->name,
                'location' => $node->location,
                'hostname' => $node->hostname,
                'panel_id' => $node->panel_id,
                'ipv4' => $node->ipv4,
                'active' => $node->active,
            ];
        }

        return $result;
    }

    /**
     * Method to list all pterodactyl panels
     * @param bool $active - if true, only active panels will be returned
     * @return array
     */
    public function panel_list($active = false): array
    {
        $panels = $this->di['db']->find('service_pterodactyl_panel');
        $result = [];
        foreach ($panels as $panel) {
            if ($active && !$panel->active) {
                continue;
            }
            $result[] = [
                'id' => $panel->id,
                'name' => $panel->name,
                'url' => $panel->url,
                'active' => $panel->active,
            ];
        }

        return $result;
    }


    /**
     * Method to get a pterodactyl node
     * @param int $node_id
     * @return array
     */
    public function node_get($node_id): array
    {
        $node = $this->di['db']->load('service_pterodactyl_node', $node_id);
        if (!$node) {
            throw new \FOSSBilling\NotFoundException('Node not found', [], 404);
        }

        return [
            'id' => $node->id,
            'name' => $node->name,
            'location' => $node->location,
            'hostname' => $node->hostname,
            'panel_id' => $node->panel_id,
            'ipv4' => $node->ipv4,
            'active' => $node->active,
        ];
    }

    /**
     * Method to get a pterodactyl panel
     * @param int $panel_id
     * @return array
     */
    public function panel_get($panel_id): array
    {
        $panel = $this->di['db']->load('service_pterodactyl_panel', $panel_id);
        if (!$panel) {
            throw new \FOSSBilling\NotFoundException('Panel not found', [], 404);
        }

        return [
            'id' => $panel->id,
            'name' => $panel->name,
            'url' => $panel->url,
            'active' => $panel->active,
        ];
    }

    /**
     * Method to create a new pterodactyl node
     * @param array $data
     * @return bool, true if the node was created successfully
     */
    public function node_create($data): bool
    {

        // Check if the required parameters are present
        $required = ['name' => 'Name', 'hostname' => 'Node hostname', 'ipv4' => 'IPv4 Address', 'panel_id' => 'Panel ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $node = $this->di['db']->dispense('service_pterodactyl_node');
        $node->name = $data['name'];
        $node->location = $data['location'];
        $node->hostname = $data['hostname'];
        $node->ipv4 = $data['ipv4'];
        $node->panel_id = $data['panel_id'];
        $node->config = json_encode($data['config']);
        $node->created_at = date('Y-m-d H:i:s');
        $node->updated_at = $node->created_at;
        $node->active = 1;
        $this->di['db']->store($node);

        $this->di['logger']->info('Pterodactyl node created', ['id' => $node->id]);

        return true;
    }
 
    /**
     * Method to create a new pterodactyl panel
     * @param array $data
     * @return bool, true if the panel was created successfully
     */
    public function panel_create($data): bool
    {

        // Check if the required parameters are present
        $required = ['name' => 'Name', 'url' => 'Panel URL', 'api_key' => 'Application API Key'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $panel = $this->di['db']->dispense('service_pterodactyl_panel');
        $panel->name = $data['name'];
        $panel->url = $data['url'];
        $panel->api_key = $data['api_key'];
        $panel->config = json_encode($data['config']);
        $panel->created_at = date('Y-m-d H:i:s');
        $panel->updated_at = $panel->created_at;
        $panel->active = 1;

        $this->di['db']->store($panel);

        $this->di['logger']->info('Pterodactyl panel created', ['id' => $panel->id]);

        return true;
    }

    /**
     * Method to update a pterodactyl node
     * @param array $data
     * @return bool, true if the node was updated successfully
     */
    public function node_update($data): bool
    {
        $required = ['id' => 'Node ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $node = $this->di['db']->load('service_pterodactyl_node', $data['id']);
        if (!$node) {
            throw new \FOSSBilling\NotFoundException('Node not found', [], 404);
        }

        $node->name = $data['name'] ?? $node->name;
        $node->location = $data['location'] ?? $node->location;
        $node->hostname = $data['hostname'] ?? $node->hostname;
        $node->ipv4 = $data['ipv4'] ?? $node->ipv4;
        $node->panel_id = $data['panel_id'] ?? $node->panel_id;
        $node->config = $data['config'] ?? $node->config;
        $node->updated_at = date('Y-m-d H:i:s');
        $node->active = $data['active'] ?? $node->active;
        $this->di['db']->store($node);

        $this->di['logger']->info('Pterodactyl node updated', ['id' => $node->id]);

        return true;
    }

    /**
     * Method to update a pterodactyl panel
     * @param array $data
     * @return bool, true if the panel was updated successfully
     */
    public function panel_update($data): bool
    {
        $required = ['id' => 'Panel ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $panel = $this->di['db']->load('service_pterodactyl_panel', $data['id']);
        if (!$panel) {
            throw new \FOSSBilling\NotFoundException('Panel not found', [], 404);
        }

        $panel->name = $data['name'] ?? $panel->name;
        $panel->url = $data['url'] ?? $panel->url;
        $panel->api_key = $data['api_key'] ?? $panel->api_key;
        $panel->config = $data['config'] ?? $panel->config;
        $panel->updated_at = date('Y-m-d H:i:s');
        $panel->active = $data['active'] ?? $panel->active;
        $this->di['db']->store($panel);

        $this->di['logger']->info('Pterodactyl panel updated', ['id' => $panel->id]);

        return true;
    }
    
    /**
     * Method to delete a pterodactyl node
     * @param array $data
     * @return bool, true if the node was deleted successfully
     */
    public function node_delete($data): bool
    {
        $required = ['id' => 'Node ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $node = $this->di['db']->load('service_pterodactyl_node', $data['id']);
        if (!$node) {
            throw new \FOSSBilling\NotFoundException('Node not found', [], 404);
        }

        $this->di['db']->trash($node);

        $this->di['logger']->info('Pterodactyl node deleted', ['id' => $data['id']]);

        return true;
    }

    /**
     * Method to delete a pterodactyl panel
     * cant delete a panel if it is in use, check if there are any nodes using this panel
     * @param array $data
     * @return bool, true if the panel was deleted successfully
     */
    public function panel_delete($data): bool
    {
        $required = ['id' => 'Panel ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $panel = $this->di['db']->load('service_pterodactyl_panel', $data['id']);
        if (!$panel) {
            throw new \FOSSBilling\NotFoundException('Panel not found', [], 404);
        }

        $nodes = $this->di['db']->find('service_pterodactyl_node', ['panel_id' => $data['id']]);
        if ($nodes) {
            throw new \FOSSBilling\InformationException('Panel is in use by nodes', [], 400);
        }

        $this->di['db']->trash($panel);

        $this->di['logger']->info('Pterodactyl panel deleted', ['id' => $data['id']]);

        return true;
    }

    /**
     * Method to test the connection to a pterodactyl panel
     * @param array $data
     * @return bool,  if the connection was successful
     */
    public function panel_test_connection($data): bool
    {
        $required = ['id' => 'Panel ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $panel = $this->di['db']->load('service_pterodactyl_panel', $data['id']);

        if (!$panel) {
            throw new \FOSSBilling\NotFoundException('Panel not found', [], 404);
        }

        $pterodactyl = new PterodactylAPI($panel->url, $panel->api_key);

        return is_numeric($pterodactyl->getServerCount());
    
    }

    /**
     * Method to test the connection to a pterodactyl node
     * @param array $data
     * @return bool, true if the connection was successful
     */
    public function node_test_connection($data): bool
    {
        $required = ['id' => 'Node ID'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $node = $this->di['db']->load('service_pterodactyl_node', $data['id']);

        if (!$node) {
            throw new \FOSSBilling\NotFoundException('Node not found', [], 404);
        }

        return PterodactylAPI::testNodeConnection($node->hostname, $node->ipv4, $node->wingsPort ?? 8080);
    }


}
