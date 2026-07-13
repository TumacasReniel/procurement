<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('anthropic');
            $table->string('model')->default('claude-haiku-4-5-20251001');
            $table->text('api_key')->nullable();
            $table->string('base_url')->nullable();
            $table->unsignedSmallInteger('max_tokens')->default(1500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('chatbot_settings')->insert([
            'provider'   => 'anthropic',
            'model'      => 'claude-haiku-4-5-20251001',
            'api_key'    => null,
            'base_url'   => null,
            'max_tokens' => 1500,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_settings');
    }
};
