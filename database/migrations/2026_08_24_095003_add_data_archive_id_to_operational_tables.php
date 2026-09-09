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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignUuid('data_archive_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('data_archives')
                ->nullOnDelete();
            $table->index('data_archive_id');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->foreignUuid('data_archive_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('data_archives')
                ->nullOnDelete();
            $table->index('data_archive_id');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreignUuid('data_archive_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('data_archives')
                ->nullOnDelete();
            $table->index('data_archive_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['data_archive_id']);
            $table->dropColumn('data_archive_id');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['data_archive_id']);
            $table->dropColumn('data_archive_id');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['data_archive_id']);
            $table->dropColumn('data_archive_id');
        });
    }
};
