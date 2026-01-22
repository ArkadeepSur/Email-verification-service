<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            if (Schema::hasColumn('webhooks', 'user_id')) {
                $table->index(['user_id', 'is_active'], 'idx_user_active');
            }
            $table->index(['event', 'is_active'], 'idx_event_active');
            $table->index('created_at');
        });

        Schema::table('verification_results', function (Blueprint $table) {
            $table->index(['user_id', 'email'], 'idx_user_email');
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            if (Schema::hasColumn('webhooks', 'user_id')) {
                $table->dropIndex('idx_user_active');
            }
            $table->dropIndex('idx_event_active');
            $table->dropIndex(['created_at']);
        });

        Schema::table('verification_results', function (Blueprint $table) {
            $table->dropIndex('idx_user_email');
            $table->dropIndex('idx_user_status');
            $table->dropIndex(['created_at']);
        });
    }
};
