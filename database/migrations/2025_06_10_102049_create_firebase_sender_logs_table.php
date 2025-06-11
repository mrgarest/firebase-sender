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
        Schema::create('firebase_sender_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_account');
            $table->string('message_id')->nullable()->default(null);
            $table->string('target', 32);
            $table->string('to');
            $table->string('payload_1')->nullable()->default(null);
            $table->string('payload_2')->nullable()->default(null);
            $table->timestamp('sent_at')->nullable()->default(null);
            $table->timestamp('failed_at')->nullable()->default(null);
            $table->timestamp('scheduled_at')->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('firebase_sender_logs');
    }
};
