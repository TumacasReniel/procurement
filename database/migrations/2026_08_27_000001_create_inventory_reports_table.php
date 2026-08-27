<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('title_id')->nullable()->index();
            $table->unsignedTinyInteger('category_id')->nullable()->index();
            $table->string('period_type')->default('monthly');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month')->nullable();
            $table->unsignedTinyInteger('period_quarter')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->string('period_label')->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('title_id')->references('id')->on('inventory_report_titles')->nullOnDelete();
            $table->foreign('category_id')->references('id')->on('list_dropdowns')->nullOnDelete();
            $table->foreign('created_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reports');
    }
};
