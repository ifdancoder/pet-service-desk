<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_violations', function (Blueprint $table) {
            $table->unique('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::table('sla_violations', function (Blueprint $table) {
            $table->dropUnique(['ticket_id']);
        });
    }
};
