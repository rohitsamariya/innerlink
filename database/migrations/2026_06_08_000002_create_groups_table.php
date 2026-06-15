<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('created_by')->constrained('users', 'id')->onDelete('restrict')->name('fk_groups_created_by');
            $table->timestampsTz(); // Native Eloquent lifecycle

            $table->unique('name', 'uq_groups_name'); // Implicitly creates Postgres B-Tree index
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
