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
        Schema::create('cas_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->json('request_payload')->nullable();
            $table->string('status', 20);
            $table->longText('output')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_token', 100)->nullable()->index();
            $table->timestamps();

            $table->index(['command', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cas_logs');
    }
};
