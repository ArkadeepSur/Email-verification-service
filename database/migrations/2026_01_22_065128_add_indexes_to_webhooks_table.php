<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->index(['user_id', 'is_active'], 'idx_user_active');
            $table->index(['event', 'is_active'], 'idx_event_active');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropIndex('idx_user_active');
            $table->dropIndex('idx_event_active');
            $table->dropIndex(['created_at']);
        });
    }
};
