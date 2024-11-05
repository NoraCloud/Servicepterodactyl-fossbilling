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
        try {
            $response = $this->panel->getServerList();
        } catch (Exception $e) {    
            if ($e->getCode() === 0 && str_contains(strtolower($e->getMessage()), 'timeout')) {
                die('Connection to the panel timed out, aborting tests');
            }
        }
        $this->assertIsArray($response);
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('object', $response['content']);
        $this->assertArrayHasKey('data', $response['content']);
        $this->assertArrayHasKey('meta', $response['content']);
        $this->assertArrayHasKey('pagination', $response['content']['meta']);
    }

    public function test_test_panel_connection_unprivileged_key() {
        $panel = new PterodactylAPI($_ENV['PTERODACTYL_URL'], $_ENV['API_KEY_UNPRIVILEGED'], !($_ENV['HTTPS'] === 'false'));
        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);
        $panel->getServerList();
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

    public function test_get_users_list() {
        $response = $this->panel->getUsersList();
        $this->assertIsArray($response);
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('object', $response['content']);
        $this->assertArrayHasKey('data', $response['content']);
        $this->assertArrayHasKey('meta', $response['content']);
        $this->assertArrayHasKey('pagination', $response['content']['meta']);
    }

    public function test_create_user_OK() {
        $email = bin2hex(random_bytes(10)) . '@email.local';
        echo "This test will create a user with email $email\n";
        $response = $this->panel->createUser($email, bin2hex(random_bytes(16)), 'myfirstname', 'mylastname');
        $this->assertIsArray($response);
        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('object', $response['content']);
        $this->assertArrayHasKey('attributes', $response['content']);
        $this->assertArrayHasKey('uuid', $response['content']['attributes']);
    }

    public function test_create_user_already_exists_email() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('Email already exists');
        $email = $this->panel->getUsersList()['content']['data'][0]['attributes']['email'];
        $this->panel->createUser($email, bin2hex(random_bytes(16)), 'myfirstname', 'mylastname');
    }

    public function test_create_user_already_exists_username() {
        $username = $this->panel->getUsersList()['content']['data'][0]['attributes']['username'];
        $response = $this->panel->createUser(bin2hex(random_bytes(16)) . '@email.local', $username, 'myfirstname', 'mylastname');
        $this->assertIsArray($response);
        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('object', $response['content']);
        $this->assertArrayHasKey('attributes', $response['content']);
        $this->assertArrayHasKey('uuid', $response['content']['attributes']);
        $this->assertNotEquals($username, $response['content']['attributes']['username']);
    }

    public function test_create_user_already_exists_email_and_username() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('Email already exists');
        $user = $this->panel->getUsersList()['content']['data'][0];
        $this->panel->createUser($user['attributes']['email'], $user['attributes']['username'], 'myfirstname', 'mylastname');
    }

    public function test_create_user_null_email() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('All fields are required');
        $this->panel->createUser("", bin2hex(random_bytes(16)), 'myfirstname', 'mylastname');
    }

    public function test_create_user_invalid_email() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Invalid email');
        $this->panel->createUser(bin2hex(random_bytes(16)), bin2hex(random_bytes(16)), 'myfirstname', 'mylastname');
    }

    public function test_create_user_null_username() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('All fields are required');
        $this->panel->createUser(bin2hex(random_bytes(10)) . '@email.local', '', 'myfirstname', 'mylastname');
    }

    public function test_find_user_by_email_OK() {
        $email = $this->panel->getUsersList()['content']['data'][0]['attributes']['email'];
        $response = $this->panel->findUserByEmail($email);
        $this->assertIsArray($response);
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('object', $response['content']['data'][0]);
        $this->assertArrayHasKey('attributes', $response['content']['data'][0]);
        $this->assertArrayHasKey('uuid', $response['content']['data'][0]['attributes']);
    }

    public function test_find_user_by_email_not_found() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);
        $this->panel->findUserByEmail(bin2hex(random_bytes(16)) . '@email.local');
    }

    public function test_find_user_by_email_null_email() {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->panel->findUserByEmail('whatisthisemail');
    }



    //test the node list, but it requires a node ID, which requires a method to list the nodes
    //TODO planned
    //idem for     public function getFirstFreeAllocationId(string $fqdn) {


    //test the find user by email method, but it requires a user email, which requires a method to list the users to get an email
    //TODO planned
    //idem for creating a user
    



}
