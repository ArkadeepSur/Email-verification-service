<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('throttle_events')) {
            Schema::create('throttle_events', function (Blueprint $table) {
                $table->id();
                $table->string('throttle_key');
                $table->string('email')->nullable();
                $table->string('ip')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('throttle_events');
    }
};
