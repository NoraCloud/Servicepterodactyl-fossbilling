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
     * Method to list all pterodactyl servers
     * @return array
     */
    public function server_list(): array
    {
        $servers = $this->di['db']->find('service_pterodactyl_node');
        $result = [];
        foreach ($servers as $server) {
            $result[] = [
                'id' => $server->id,
                'name' => $server->name,
                'location' => $server->location,
                'hostname' => $server->hostname,
                'ipv4' => $server->ipv4,
                'active' => $server->active,
            ];
        }

        return $result;
    }

    /**
     * Method to list all pterodactyl panels
     * @return array
     */
    public function panel_list(): array
    {
        $panels = $this->di['db']->find('service_pterodactyl_panel');
        $result = [];
        foreach ($panels as $panel) {
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
     * Method to create a new pterodactyl server
     * @param array $data
     * @return bool, true if the server was created successfully
     */
    public function server_create($data): bool
    {

        // Check if the required parameters are present
        $required = ['name' => 'Name', 'hostname' => 'Node hostname', 'ipv4' => 'IPv4 Address'];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $server = $this->di['db']->dispense('service_pterodactyl_node');
        $server->name = $data['name'];
        $server->location = $data['location'];
        $server->hostname = $data['hostname'];
        $server->ipv4 = $data['ipv4'];
        $server->created_at = date('Y-m-d H:i:s');
        $server->updated_at = $server->created_at;
        $server->active = 1;
        $this->di['db']->store($server);

        $this->di['logger']->info('Pterodactyl server created', ['id' => $server->id]);

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
        $panel->created_at = date('Y-m-d H:i:s');
        $panel->updated_at = $panel->created_at;
        $panel->active = 1;

        $this->di['db']->store($panel);

        $this->di['logger']->info('Pterodactyl panel created', ['id' => $panel->id]);

        return true;
    }

}
