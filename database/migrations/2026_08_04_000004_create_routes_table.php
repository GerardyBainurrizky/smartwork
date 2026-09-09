<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->date('date');
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->decimal('distance_planned', 10, 2)->nullable();
            $table->decimal('distance_actual', 10, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date');
            $table->index('status');
            $table->index(['user_id', 'date']);
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('route_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['route_id', 'sequence']);
            $table->index(['route_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('routes');
    }
};