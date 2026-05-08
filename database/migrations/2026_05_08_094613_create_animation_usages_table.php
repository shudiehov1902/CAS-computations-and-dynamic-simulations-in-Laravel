<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('animation_usages', function (Blueprint $table) {
            $table->id();
            $table->string('animation_type', 50);
            $table->string('user_token', 100)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('city')->default('Unknown');
            $table->string('country')->default('Unknown');
            $table->timestamp('used_at')->index();
            $table->timestamps();

            $table->index(['animation_type', 'user_token', 'used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animation_usages');
    }
};
