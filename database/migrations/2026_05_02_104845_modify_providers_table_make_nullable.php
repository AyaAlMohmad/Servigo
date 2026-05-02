<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('id_photo_front')->nullable()->change();
            $table->string('id_photo_back')->nullable()->change();
            $table->string('rejection_reason')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('id_photo_front')->nullable(false)->change();
            $table->string('id_photo_back')->nullable(false)->change();
            $table->string('rejection_reason')->nullable(false)->change();
        });
    }
};