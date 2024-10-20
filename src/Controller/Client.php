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
 * This file connects FOSSBilling client area interface and API
 * Class does not extend any other class.
 */

namespace Box\Mod\Servicepterodactyl\Controller;

class Client implements \FOSSBilling\InjectionAwareInterface
{
    protected $di;

    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    /**
     * Methods maps client areas urls to corresponding methods
     * Always use your module prefix to avoid conflicts with other modules
     * in future.
     *
     * @param \Box_App $app - returned by reference
     */
    public function register(\Box_App &$app): void
    {
        $app->get('/servicepterodactyl', 'get_index', [], static::class);
        $app->get('/servicepterodactyl/protected', 'get_protected', [], static::class);
    }

    public function get_index(\Box_App $app)
    {
        return $app->render('mod_servicepterodactyl_index');
    }

    public function get_protected(\Box_App $app)
    {
        // call $this->di['is_client_logged'] method to validate if client is logged in
        $this->di['is_client_logged'];

        return $app->render('mod_servicepterodactyl_index', ['show_protected' => true]);
    }
}
