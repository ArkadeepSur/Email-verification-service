<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            if (!Schema::hasColumn('webhooks', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
