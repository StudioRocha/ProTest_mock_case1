<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->unsignedInteger('total_amount');
            $table->string('payment_status', 32)->default('paid');
            $table->string('trade_status', 32)->nullable();
            $table->string('payment_method', 50);
            $table->text('shipping_address');
            $table->text('comment')->nullable();
            $table->timestamp('buyer_last_viewed_at')->nullable()->comment('購入者が最後にチャット画面を閲覧した時刻');
            $table->timestamp('seller_last_viewed_at')->nullable()->comment('出品者が最後にチャット画面を閲覧した時刻');
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
        Schema::dropIfExists('orders');
    }
}
