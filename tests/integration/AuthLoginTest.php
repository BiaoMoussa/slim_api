<?php

declare(strict_types=1);

namespace Tests\integration;

class AuthLoginTest extends BaseTestCase
{
    /**
     * Test user login endpoint and get a JWT Bearer Authorization.
     */
    public function testLogin(): void
    {
        $response = $this->runApp('POST', '/login', ['login' => 'bbm', 'password' => 'Deafult2024']);

        $result = (string) $response->getBody();
        self::$jwt ="Bearer ". json_decode($result)->message->Authorization;
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Authorization',$result);
    }
}
