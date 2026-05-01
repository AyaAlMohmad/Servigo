<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'admin_pinned']);
            $table->foreignId('participant_one')->constrained('users')->onDelete('cascade');
            $table->foreignId('participant_two')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
        
            $table->unique(['participant_one', 'participant_two']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
    }
};