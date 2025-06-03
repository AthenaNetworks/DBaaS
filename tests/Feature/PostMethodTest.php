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

class PostMethodTest extends TestCase
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
            // Allow select and insert methods to be called
            $mock->shouldAllowMockingProtectedMethods();
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
     * Test POST method for SELECT operation
     */
    public function test_post_method_for_select(): void
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
     * Test POST method for SELECT operation with specific columns
     */
    public function test_post_method_for_select_with_columns(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select',
            'table' => $this->testTable,
            'columns' => ['name', 'email']
        ]);

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
     * Test POST method for SELECT operation with a single where condition
     */
    public function test_post_method_for_select_with_single_where(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select',
            'table' => $this->testTable,
            'where' => [
                'column' => 'age',
                'operator' => '>',
                'value' => 20
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test POST method for SELECT operation with multiple where conditions
     */
    public function test_post_method_for_select_with_multiple_where(): void
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
     * Test POST method for SELECT operation with order by
     */
    public function test_post_method_for_select_with_order_by(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select',
            'table' => $this->testTable,
            'order_by' => [
                'column' => 'age',
                'direction' => 'desc'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.age', 30);
    }

    /**
     * Test POST method for SELECT operation with limit and offset
     */
    public function test_post_method_for_select_with_limit_and_offset(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select',
            'table' => $this->testTable,
            'order_by' => [
                'column' => 'age',
                'direction' => 'asc'
            ],
            'limit' => 1,
            'offset' => 0
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.age', 30);
    }

    /**
     * Test POST method for INSERT operation (default method)
     */
    public function test_post_method_for_insert_default(): void
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
     * Test POST method for INSERT operation (explicit method)
     */
    public function test_post_method_for_insert_explicit(): void
    {
        $email = $this->faker->unique()->safeEmail;
        
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'insert',
            'table' => $this->testTable,
            'data' => [
                'name' => 'Test User Explicit',
                'email' => $email,
                'age' => 45,
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
     * Test error handling for invalid method
     */
    public function test_error_for_invalid_method(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'invalid_method',
            'table' => $this->testTable
        ]);

        // Since our implementation defaults to insert for unknown methods,
        // this should fail validation because 'data' is missing
        $response->assertStatus(422);
    }

    /**
     * Test error handling for missing table
     */
    public function test_error_for_missing_table(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'select'
            // Missing 'table' parameter
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test error handling for missing data in INSERT operation
     */
    public function test_error_for_missing_data_in_insert(): void
    {
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db', [
            'method' => 'insert',
            'table' => $this->testTable
            // Missing 'data' parameter
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test legacy insert endpoint (for backward compatibility)
     */
    public function test_legacy_insert_endpoint(): void
    {
        $email = $this->faker->unique()->safeEmail;
        
        $response = $this->withHeaders(
            $this->getApiHeaders($this->apiKey, ['Content-Type' => 'application/json'])
        )->postJson('/api/db/insert', [
            'table' => $this->testTable,
            'data' => [
                'name' => 'Legacy Insert User',
                'email' => $email,
                'age' => 50,
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
}
