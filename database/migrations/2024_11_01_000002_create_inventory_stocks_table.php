<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('inventory_stocks')) {
            return;
        }

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('location_id')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('status')->default('available'); // available, low, out, reserved
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();

            $table->foreign('location_id')
                ->references('id')
                ->on('list_dropdowns')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_stocks');
    }
};


