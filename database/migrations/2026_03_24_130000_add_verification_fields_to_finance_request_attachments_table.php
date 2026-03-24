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
        Schema::table('finance_request_attachments', function (Blueprint $table) {
            $table->string('verification_status', 20)->default('pending')->after('uploaded_by_id');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->unsignedInteger('verified_by_id')->nullable()->after('verification_note');
            $table->dateTime('verified_at')->nullable()->after('verified_by_id');

            $table->foreign('verified_by_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_request_attachments', function (Blueprint $table) {
            $table->dropForeign(['verified_by_id']);
            $table->dropColumn([
                'verification_status',
                'verification_note',
                'verified_by_id',
                'verified_at',
            ]);
        });
    }
};
