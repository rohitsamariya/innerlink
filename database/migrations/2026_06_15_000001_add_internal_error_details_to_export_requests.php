<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_requests', function (Blueprint $table) {
            $table->text('internal_error_details')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('export_requests', function (Blueprint $table) {
            $table->dropColumn('internal_error_details');
        });
    }
};
