<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Illuminate\Database\QueryException;

class DatabaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $databaseService;
    protected $user;
    protected $testTable = 'test_service_table';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with admin role
        $this->user = User::factory()->create([
            'role' => 'admin',
            'api_key' => 'test-service-api-key'
        ]);
        
        // Create a test table for our operations
        if (!Schema::hasTable($this->testTable)) {
            Schema::create($this->testTable, function ($table) {
                $table->id();
                $table->string('name');
                $table->integer('age')->nullable();
                $table->string('email')->unique();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
        
        // Insert some test data
        DB::table($this->testTable)->insert([
            ['name' => 'John Doe', 'age' => 30, 'email' => 'john.service@example.com', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Jane Smith', 'age' => 25, 'email' => 'jane.service@example.com', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bob Johnson', 'age' => 40, 'email' => 'bob.service@example.com', 'active' => false, 'created_at' => now(), 'updated_at' => now()]
        ]);
        
        // Create the real service instance
        $this->databaseService = new DatabaseService();
    }

    protected function tearDown(): void
    {
        // Clean up the test table
        Schema::dropIfExists($this->testTable);
        
        parent::tearDown();
    }

    /**
     * Test basic select operation
     */
    public function test_basic_select(): void
    {
        $results = $this->databaseService->select(
            $this->testTable,
            ['name', 'email'],
            [],
            [],
            null,
            0,
            $this->user
        );
        
        $this->assertCount(3, $results);
        $this->assertEquals('John Doe', $results[0]->name);
        $this->assertEquals('john.service@example.com', $results[0]->email);
    }

    /**
     * Test select with where condition
     */
    public function test_select_with_where(): void
    {
        $results = $this->databaseService->select(
            $this->testTable,
            ['name', 'email'],
            [
                [
                    'column' => 'age',
                    'operator' => '>',
                    'value' => 25
                ]
            ],
            [],
            null,
            0,
            $this->user
        );
        
        $this->assertCount(2, $results);
    }

    /**
     * Test select with complex where conditions
     */
    public function test_select_with_complex_where(): void
    {
        $results = $this->databaseService->select(
            $this->testTable,
            ['name', 'email'],
            [
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
            ],
            [],
            null,
            0,
            $this->user
        );
        
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]->name);
    }

    /**
     * Test select with order by
     */
    public function test_select_with_order_by(): void
    {
        $results = $this->databaseService->select(
            $this->testTable,
            ['name', 'age'],
            [],
            [
                [
                    'column' => 'age',
                    'direction' => 'desc'
                ]
            ],
            null,
            0,
            $this->user
        );
        
        $this->assertCount(3, $results);
        $this->assertEquals(40, $results[0]->age);
        $this->assertEquals(30, $results[1]->age);
        $this->assertEquals(25, $results[2]->age);
    }

    /**
     * Test select with limit and offset
     */
    public function test_select_with_limit_and_offset(): void
    {
        $results = $this->databaseService->select(
            $this->testTable,
            ['name', 'age'],
            [],
            [
                [
                    'column' => 'age',
                    'direction' => 'asc'
                ]
            ],
            1,
            1,
            $this->user
        );
        
        $this->assertCount(1, $results);
        $this->assertEquals(30, $results[0]->age);
    }

    /**
     * Test insert operation
     */
    public function test_insert(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test.service@example.com',
            'age' => 35,
            'active' => true
        ];
        
        $id = $this->databaseService->insert(
            $this->testTable,
            $data,
            $this->user
        );
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        // Verify the record was inserted
        $this->assertDatabaseHas($this->testTable, [
            'name' => 'Test User',
            'email' => 'test.service@example.com'
        ]);
    }

    /**
     * Test update operation
     */
    public function test_update(): void
    {
        $affected = $this->databaseService->update(
            $this->testTable,
            ['age' => 31],
            [
                [
                    'column' => 'email',
                    'operator' => '=',
                    'value' => 'john.service@example.com'
                ]
            ],
            false,
            $this->user
        );
        
        $this->assertEquals(1, $affected);
        
        // Verify the record was updated
        $this->assertDatabaseHas($this->testTable, [
            'email' => 'john.service@example.com',
            'age' => 31
        ]);
    }

    /**
     * Test delete operation
     */
    public function test_delete(): void
    {
        $affected = $this->databaseService->delete(
            $this->testTable,
            [
                [
                    'column' => 'email',
                    'operator' => '=',
                    'value' => 'bob.service@example.com'
                ]
            ],
            $this->user
        );
        
        $this->assertEquals(1, $affected);
        
        // Verify the record was deleted
        $this->assertDatabaseMissing($this->testTable, [
            'email' => 'bob.service@example.com'
        ]);
    }

    /**
     * Test validation for non-existent tables
     */
    public function test_validation_for_nonexistent_tables(): void
    {
        $this->expectException(QueryException::class);
        
        // Try to select from a non-existent table
        $this->databaseService->select(
            'nonexistent_table',
            ['*'],
            [],
            [],
            null,
            0,
            $this->user
        );
    }
}
