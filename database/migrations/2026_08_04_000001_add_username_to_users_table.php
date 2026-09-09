<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('id');
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar', 500)->nullable()->after('phone');
            $table->string('status', 20)->default('active')->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->softDeletes();
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('username')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'avatar', 'status',
                'last_login_at', 'last_login_ip', 'deleted_at',
            ]);
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};