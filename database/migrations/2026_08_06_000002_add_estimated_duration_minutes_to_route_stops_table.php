<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable()->after('sequence');
        });

        if (Schema::hasColumn('route_stops', 'estimated_time')) {
            Schema::table('route_stops', function (Blueprint $table) {
                $table->dropColumn('estimated_time');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('route_stops', 'estimated_duration_minutes')) {
            Schema::table('route_stops', function (Blueprint $table) {
                $table->dropColumn('estimated_duration_minutes');
            });
        }

        Schema::table('route_stops', function (Blueprint $table) {
            $table->time('estimated_time')->nullable()->after('sequence');
        });
    }
};