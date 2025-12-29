<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('verification_results', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('status')->nullable();
            $table->integer('risk_score')->nullable();
            $table->json('details')->nullable();
            $table->string('job_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('verification_results');
    }
};
