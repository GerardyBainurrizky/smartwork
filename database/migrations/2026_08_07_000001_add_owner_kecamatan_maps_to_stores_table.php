<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('owner', 200)->nullable()->after('name');
            $table->string('kecamatan', 100)->nullable()->after('city');
            $table->string('maps_url', 500)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['owner', 'kecamatan', 'maps_url']);
        });
    }
};
