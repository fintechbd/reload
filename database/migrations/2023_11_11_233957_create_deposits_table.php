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
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_country_id')->nullable();
                $table->foreignId('destination_country_id')->nullable();
                $table->foreignId('parent_id')->nullable();
                $table->foreignId('sender_receiver_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('service_id')->nullable();
                $table->foreignId('transaction_form_id')->nullable();
                $table->dateTime('ordered_at')->nullable();
                $table->decimal('amount', 19, 6)->nullable();
                $table->string('currency', 3)->nullable();
                $table->decimal('converted_amount', 19, 6)->nullable();
                $table->string('converted_currency', 3)->nullable();
                $table->string('order_number')->nullable();
                $table->string('risk_profile')->default('green')->nullable();
                $table->longText('notes')->nullable();
                $table->boolean('is_refunded')->nullable()->default(false)->comment('if money has refunded');
                $table->json('order_data')->nullable();
                $table->string('status')->default(\Fintech\Core\Enums\Transaction\OrderStatus::Processing->value);
                $table->foreignId('creator_id')->nullable();
                $table->foreignId('editor_id')->nullable();
                $table->foreignId('destroyer_id')->nullable();
                $table->foreignId('restorer_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->timestamp('restored_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
