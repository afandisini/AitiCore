<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use System\Http\Request;

class RouterTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = dirname(__DIR__, 2);
        $_ENV = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public function testHomeRouteReturns200(): void
    {
        $app = require $this->basePath . '/bootstrap/app.php';
        $response = $app->kernel()->handle(Request::create('GET', '/'));

        $this->assertSame(200, $response->statusCode());
        $this->assertStringContainsString('AitiCore Flex', $response->content());
    }

    public function testHeadRequestUsesGetRouteWithoutBody(): void
    {
        $app = require $this->basePath . '/bootstrap/app.php';
        $response = $app->kernel()->handle(Request::create('HEAD', '/'));

        $this->assertSame(200, $response->statusCode());
        $this->assertSame('', $response->content());
    }

    public function testMissingRouteUses404View(): void
    {
        $app = require $this->basePath . '/bootstrap/app.php';
        $response = $app->kernel()->handle(Request::create('GET', '/missing-page'));

        $this->assertSame(404, $response->statusCode());
        $this->assertStringContainsString('404', $response->content());
        $this->assertStringContainsString('Halaman tidak ditemukan', $response->content());
    }

    public function testRouterScriptLetsPublicFilesPassThrough(): void
    {
        $_SERVER['REQUEST_URI'] = '/favicon.ico';

        $result = require $this->basePath . '/router.php';

        $this->assertFalse($result);
    }
}
