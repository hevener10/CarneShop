<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_path_returns_the_api_payload_when_the_spa_is_not_built(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertJson([
            'name' => 'CarneShop API',
            'version' => '1.0.0',
        ]);
    }

    public function test_the_health_endpoint_returns_the_expected_payload(): void
    {
        $response = $this->get('/health');

        $response->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_spa_routes_serve_the_built_frontend_when_available(): void
    {
        $spaEntry = public_path('index.html');
        $originalContents = file_exists($spaEntry) ? file_get_contents($spaEntry) : null;

        file_put_contents($spaEntry, '<!DOCTYPE html><html><body>CarneShop Web</body></html>');

        try {
            $response = $this->get('/admin/orders');

            $response->assertOk()->assertSee('CarneShop Web', false);
            $this->assertStringStartsWith('text/html', $response->headers->get('content-type', ''));
        } finally {
            if ($originalContents === null) {
                @unlink($spaEntry);

                return;
            }

            file_put_contents($spaEntry, $originalContents);
        }
    }
}
