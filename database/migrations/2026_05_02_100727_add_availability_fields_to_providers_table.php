<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
       
            $table->json('off_days')->nullable(); // ['monday', 'wednesday']
           
        
            $table->boolean('is_available')->default(true);
        });
    }

    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([ 'off_days','is_available']);
        });
    }
};