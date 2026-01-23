<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_image_paths');
            $table->string('item_names', 255);
            $table->string('brand_names', 255);
            $table->unsignedInteger('item_prices');
            $table->unsignedInteger('like_counts')->default(0);
            $table->unsignedInteger('comment_counts')->default(0);
            $table->string('item_descriptions', 255);
            $table->unsignedTinyInteger('conditions');
            $table->boolean('is_sold')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
}
