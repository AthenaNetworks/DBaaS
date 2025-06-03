<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('table_name');
            $table->boolean('can_select')->default(false);
            $table->boolean('can_insert')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->json('where_conditions')->nullable()->comment('JSON conditions that must be met for operations');
            $table->json('column_restrictions')->nullable()->comment('JSON defining allowed or denied columns');
            $table->timestamps();
            
            // Unique constraint to ensure one permission set per user per table
            $table->unique(['user_id', 'table_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
