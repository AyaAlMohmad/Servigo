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
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
