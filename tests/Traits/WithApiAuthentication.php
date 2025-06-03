<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Config;

trait WithApiAuthentication
{
    /**
     * Create a test user with admin role and API key
     *
     * @return User
     */
    protected function createTestUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'api_key' => 'test-api-key-' . uniqid()
        ]);
    }
    
    /**
     * Set up API authentication for tests
     *
     * @param User|null $user
     * @return string
     */
    protected function setupApiAuthentication(?User $user = null): string
    {
        $testUser = $user ?? $this->createTestUser();
        return $testUser->api_key;
    }
    
    /**
     * Get API headers with authentication
     *
     * @param string $apiKey
     * @param array $additionalHeaders
     * @return array
     */
    protected function getApiHeaders(string $apiKey, array $additionalHeaders = []): array
    {
        return array_merge([
            'X-API-Key' => $apiKey,
            'Accept' => 'application/json',
        ], $additionalHeaders);
    }
}
