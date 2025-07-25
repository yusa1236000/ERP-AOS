<?php
// database/migrations/2024_01_01_000002_create_permissions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('module'); // inventory, manufacturing, sales, etc.
            $table->string('action'); // create, read, update, delete, approve, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module', 'action']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('permissions');
    }
};
