<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->cascadeOnDelete();
            $table->string('priority');
            $table->unsignedInteger('response_time_minutes');
            $table->unsignedInteger('resolution_time_minutes');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
