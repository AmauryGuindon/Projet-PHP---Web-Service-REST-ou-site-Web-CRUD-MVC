<?php
namespace Tests\Feature;

use App\Services\ExternalOddsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalApiTest extends TestCase
{
    public function test_external_sports_endpoint_returns_data(): void
    {
        Http::fake([
            '*the-odds-api*' => Http::response([
                ['key' => 'soccer', 'title' => 'Soccer', 'active' => true],
            ], 200),
        ]);

        $this->getJson('/api/v1/external/sports')
            ->assertOk()
            ->assertJsonStructure(['source', 'count', 'data']);
    }

    public function test_external_service_returns_fallback_on_api_failure(): void
    {
        Http::fake([
            '*the-odds-api*' => Http::response([], 500),
        ]);

        $this->getJson('/api/v1/external/sports')
            ->assertOk()
            ->assertJsonPath('source', 'external_api');
    }

    public function test_factory_method_normalizes_external_odd(): void
    {
        $raw = ['home' => 1.95, 'draw' => 3.20, 'away' => 4.10, 'bookmaker' => 'TestBook'];
        $odd = ExternalOddsService::createOddFromExternal($raw);

        $this->assertEquals(1.95, $odd['home_win']);
        $this->assertEquals('external', $odd['source']);
        $this->assertEquals('TestBook', $odd['bookmaker']);
    }
}
