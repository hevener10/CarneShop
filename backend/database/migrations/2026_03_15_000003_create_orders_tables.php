<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabelas de pedidos
     */
    public function up(): void
    {
        // Pedidos
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            
            // Endereço de entrega
            $table->string('delivery_address')->nullable();
            $table->string('delivery_number')->nullable();
            $table->string('delivery_complement')->nullable();
            $table->string('delivery_neighborhood')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_state', 2)->nullable();
            $table->string('delivery_reference')->nullable();
            
            // Valores
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            
            // Pagamento
            $table->enum('payment_method', ['money', 'pix', 'card', 'transfer'])->nullable();
            $table->decimal('change_for', 10, 2)->nullable(); // troco para
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            
            // Status do pedido
            $table->enum('status', [
                'pending',      // Pendente
                'confirmed',    // Confirmado
                'preparing',    // Preparando
                'ready',        // Pronto para entrega
                'dispatched',   // Enviado/Entregue
                'canceled'      // Cancelado
            ])->default('pending');
            
            $table->text('admin_notes')->nullable(); // observações do admin
            $table->text('cancellation_reason')->nullable();
            
            // Dados do WhatsApp
            $table->text('whatsapp_message')->nullable(); // mensagem enviada
            
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'created_at']);
        });

        // Itens do pedido
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variation_name')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->integer('gramage')->nullable(); // peso em gramas
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        // Configurações da loja
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('store_settings');
    }
};
