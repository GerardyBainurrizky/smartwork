<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('transaction_status', 20)->nullable()->default('none')->after('payment_method');
        });

        \Illuminate\Support\Facades\DB::table('visits')
            ->whereNotNull('transaction_amount')
            ->where('transaction_amount', '>', 0)
            ->update(['transaction_status' => 'paid']);
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('transaction_status');
        });
    }
};
