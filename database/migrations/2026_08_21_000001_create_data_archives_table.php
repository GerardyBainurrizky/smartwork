<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status')->index();
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status')->index();
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('status')->index();
        });

        Schema::create('data_archives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('data_type', 50); // 'attendance', 'route', 'visit', 'all'
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('records_count')->default(0);
            $table->json('details')->nullable(); // detail per model count
            $table->string('status', 20)->default('archived'); // 'archived', 'restored', 'purged'
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();

            $table->index(['data_type', 'status']);
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_archives');

        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
