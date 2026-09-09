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
        Schema::table('store_transactions', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('created_by')->index();
            $table->foreignUuid('data_archive_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('data_archives')
                ->nullOnDelete();
            $table->index('data_archive_id');
        });

        Schema::table('store_transaction_payments', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('notes')->index();
            $table->foreignUuid('data_archive_id')
                ->nullable()
                ->after('archived_at')
                ->constrained('data_archives')
                ->nullOnDelete();
            $table->index('data_archive_id');
        });

        Schema::table('store_receivables', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('created_by')->index();
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
        Schema::table('store_receivables', function (Blueprint $table) {
            $table->dropForeign(['data_archive_id']);
            $table->dropColumn(['archived_at', 'data_archive_id']);
        });

        Schema::table('store_transaction_payments', function (Blueprint $table) {
            $table->dropForeign(['data_archive_id']);
            $table->dropColumn(['archived_at', 'data_archive_id']);
        });

        Schema::table('store_transactions', function (Blueprint $table) {
            $table->dropForeign(['data_archive_id']);
            $table->dropColumn(['archived_at', 'data_archive_id']);
        });
    }
};
