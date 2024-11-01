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

use PHPUnit\Framework\TestCase;

final class PterodactylTest extends TestCase {

    
    protected function setUp(): void
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    }


    public function testAssertEquals() {
        $this->assertEquals(1, 1);
    }

}
