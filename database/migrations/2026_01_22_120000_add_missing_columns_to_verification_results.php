<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('verification_results', function (Blueprint $table) {
            if (! Schema::hasColumn('verification_results', 'syntax_valid')) {
                $table->boolean('syntax_valid')->nullable();
            }
            if (! Schema::hasColumn('verification_results', 'smtp')) {
                $table->string('smtp')->nullable();
            }
            if (! Schema::hasColumn('verification_results', 'catch_all')) {
                $table->boolean('catch_all')->nullable();
            }
            if (! Schema::hasColumn('verification_results', 'disposable')) {
                $table->boolean('disposable')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('verification_results', function (Blueprint $table) {
            $table->dropColumnIfExists('syntax_valid');
            $table->dropColumnIfExists('smtp');
            $table->dropColumnIfExists('catch_all');
            $table->dropColumnIfExists('disposable');
        });
    }
};
