<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('off_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->enum('day', ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $table->timestamps(); // created_at فقط, ولكن نستخدم timestamps للتطابق العام
            $table->unique(['provider_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('off_days');
    }
};
