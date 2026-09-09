<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visit_photos')) {
            Schema::create('visit_photos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('visit_id')->constrained('visits')->cascadeOnDelete();
                $table->string('type', 50)->default('checkout_documentation');
                $table->string('photo_path', 500);
                $table->string('caption', 255)->nullable();
                $table->timestamps();

                $table->index(['visit_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_photos');
    }
};
