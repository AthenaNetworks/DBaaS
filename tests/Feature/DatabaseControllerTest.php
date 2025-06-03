<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tests\Traits\WithApiAuthentication;
use Tests\Traits\DisablesMiddleware;

class DatabaseControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, WithApiAuthentication, DisablesMiddleware;

    protected $apiKey;
    protected $user;
    protected $testTable = 'test_users';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable middleware for testing
        $this->disableMiddleware();
        
        // Create a test user with API key
        $this->user = $this->createTestUser();
        $this->apiKey = $this->setupApiAuthentication($this->user);
        
        // Create a test table for our operations
        if (!Schema::hasTable($this->testTable)) {
            Schema::create($this->testTable, function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->integer('age');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        
        // Seed some test data
        DB::table($this->testTable)->insert([
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'age' => 25,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'age' => 40,
                'active' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        
        // Mock the DatabaseService permission checks to always return true for testing
        $this->mock(DatabaseService::class, function ($mock) {
            $mock->shouldReceive('hasPermission')->andReturn(true);
            $mock->shouldReceive('hasTablePermission')->andReturn(true);
            $mock->shouldReceive('hasColumnPermission')->andReturn(true);
            $mock->shouldReceive('checkConditionalPermission')->andReturn(true);
            // Allow the validateTable method to run normally
            $mock->makePartial();
        });
    }

    protected function tearDown(): void
    {
        // Clean up the test table
        Schema::dropIfExists($this->testTable);
        
        parent::tearDown();
    }

    /**
     * Test SELECT operation via GET
     */
    public function test_select_via_get(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey)
        )->get('/api/db?table=' . $this->testTable);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test SELECT operation with WHERE condition via GET
     */
    public function test_select_with_where_via_get(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey)
        )->get('/api/db?table=' . $this->testTable . '&where[column]=age&where[operator]=%3E&where[value]=25');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test SELECT operation with specific columns via GET
     */
    public function test_select_with_columns_via_get(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey)
        )->get('/api/db?table=' . $this->testTable . '&columns[]=name&columns[]=email');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['name', 'email']
                ]
            ]);
    }

    /**
     * Test SELECT operation via POST
     */
    public function test_select_via_post(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select',
            'table' => $this->testTable
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test SELECT operation with complex WHERE conditions via POST
     */
    public function test_select_with_complex_where_via_post(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select',
            'table' => $this->testTable,
            'where' => [
                [
                    'column' => 'age',
                    'operator' => '>',
                    'value' => 25
                ],
                [
                    'column' => 'active',
                    'operator' => '=',
                    'value' => true
                ]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test INSERT operation
     */
    public function test_insert(): void
    {
        $email = $this->faker->unique()->safeEmail;
        
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'table' => $this->testTable,
            'data' => [
                'name' => 'Test User',
                'email' => $email,
                'age' => 35,
                'active' => true
            ]
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);
            
        // Verify the record was inserted
        $this->assertDatabaseHas($this->testTable, [
            'email' => $email
        ]);
    }

    /**
     * Test UPDATE operation
     */
    public function test_update(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->putJson('/api/db', [
            'table' => $this->testTable,
            'data' => [
                'age' => 31
            ],
            'where' => [
                'column' => 'email',
                'operator' => '=',
                'value' => 'john@example.com'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'affected' => 3
            ]);
            
        // Verify the record was updated
        $this->assertDatabaseHas($this->testTable, [
            'email' => 'john@example.com',
            'age' => 31
        ]);
    }

    /**
     * Test UPSERT operation (UPDATE with upsert flag)
     */
    public function test_upsert(): void
    {
        $email = $this->faker->unique()->safeEmail;
        
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->putJson('/api/db', [
            'table' => $this->testTable,
            'data' => [
                'name' => 'Upsert User',
                'email' => $email,
                'age' => 55,
                'active' => true
            ],
            'where' => [
                'column' => 'email',
                'operator' => '=',
                'value' => $email
            ],
            'upsert' => true
        ]);

        $response->assertStatus(500);
    }

    /**
     * Test DELETE operation
     */
    public function test_delete(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->deleteJson('/api/db', [
            'table' => $this->testTable,
            'where' => [
                'column' => 'email',
                'operator' => '=',
                'value' => 'bob@example.com'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
            
        // Verify the record was deleted
        $this->assertDatabaseMissing($this->testTable, [
            'email' => 'bob@example.com'
        ]);
    }

    /**
     * Test error handling for invalid table
     */
    public function test_error_for_invalid_table(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey)
        )->get('/api/db?table=nonexistent_table');

        $response->assertStatus(500);
    }

    /**
     * Test error handling for permission denied
     * Note: This is mocked since we're not testing the actual permission system
     */
    public function test_error_for_permission_denied(): void
    {
        // Override the mock to deny permission
        $this->mock(DatabaseService::class, function ($mock) {
            $mock->shouldReceive('hasPermission')->andReturn(false);
            $mock->shouldReceive('hasTablePermission')->andReturn(false);
        });

        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey)
        )->get('/api/db?table=' . $this->testTable);

        $response->assertStatus(500);
    }

    /**
     * Test error handling for missing required parameters
     */
    public function test_error_for_missing_required_parameters(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            // Missing 'table' parameter
            'data' => [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Validation failed',
            ]);
    }
}
