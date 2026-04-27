<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->foreignId('sub_service_id')->nullable()->constrained('sub_services');
            $table->text('location_description')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('min_price', 10, 2)->nullable();
            $table->decimal('max_price', 10, 2)->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->boolean('overnight')->default(false);
            $table->text('about_me')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['sub_service_id']);
            $table->dropColumn([
                'sub_service_id',
                'location_description',
                'currency',
                'min_price',
                'max_price',
                'work_start_time',
                'work_end_time',
                'overnight',
                'about_me'
            ]);
        });
    }
};
