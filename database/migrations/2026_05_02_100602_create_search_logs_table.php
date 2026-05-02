<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('main_service_id')->constrained('services');
            $table->foreignId('sub_service_id')->constrained('sub_services');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_logs');
    }
};