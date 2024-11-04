<?php declare(strict_types=1);

/**
 * Pterodactyl Test class 
 * Used to test pterodactyl API methods
 *
 * @copyright NoraCloud 2024 (https://www.noracloud.fr)
 *
 * Copyright 2024 NoraCloud
 *
 */

include('src/Pterodactyl.php');
use Box\Mod\Servicepterodactyl\PterodactylAPI;
use PHPUnit\Framework\TestCase;

final class PterodactylTest extends TestCase {

    private PterodactylAPI $panel;
    
    protected function setUp(): void
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
        $this->panel = new PterodactylAPI($_ENV['PTERODACTYL_URL'], $_ENV['API_KEY'], !($_ENV['HTTPS'] === 'false'));
    }


    public function test_assert_equals() {
        $this->assertEquals(1, 1);
    }

    public function test_test_panel_connection_OK() {
        $response = $this->panel->getServerList();
        $this->assertIsArray($response);
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('object', $response['content']);
        $this->assertArrayHasKey('data', $response['content']);
        $this->assertArrayHasKey('meta', $response['content']);
        $this->assertArrayHasKey('pagination', $response['content']['meta']);
    }

    public function test_test_panel_connection_invalid_url() {
        $panel = new PterodactylAPI('invalid_url', 'invalid_key');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(0);
        $panel->getServerList();
    }

    public function test_test_panel_connection_invalid_key() {
        $panel = new PterodactylAPI($_ENV['PTERODACTYL_URL'], 'invalid_key', !($_ENV['HTTPS'] === 'false'));
        $this->expectException(Exception::class);
        $this->expectExceptionCode(401);
        $panel->getServerList();
    }

    public function test_get_servers_count() {
        $response = $this->panel->getServerCount();
        $this->assertIsInt($response);
    }

    public function test_get_eggs_list() {
        $response = $this->panel->getEggsList();
        $this->assertIsArray($response);
        $this->assertEquals(200, $response['status']);
        $this->assertIsArray($response['content']);
    }

    //test the node list, but it requires a node ID, which requires a method to list the nodes
    //TODO planned

    //test the find user by email method, but it requires a user email, which requires a method to list the users to get an email
    //TODO planned
    //idem for creating a user
    



}
