<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabelas específicas de cada loja (tenant)
     */
    public function up(): void
    {
        // Categorias de produtos
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'is_active']);
        });

        // Produtos
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // preço por kg
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('stock')->nullable(); // estoque (null = ilimitado)
            $table->integer('min_gramage')->default(500); // peso mínimo em gramas
            $table->integer('max_gramage')->default(5000); // peso máximo em gramas
            $table->integer('gramage_step')->default(500); // step de gramatura
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category_id']);
        });

        // Variações de produto (bife, moído, cubos, etc)
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Bife, Moído, Cubos, Inteiro, etc
            $table->decimal('price_adjust', 10, 2)->default(0); // ajuste de preço
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Observações personalizadas (mais gordo, mais fino, etc)
        Schema::create('product_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Kits/Combos
        Schema::create('kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('items_list')->nullable(); // lista de itens em texto
            $table->decimal('price', 10, 2);
            $table->decimal('price_per_person', 10, 2)->nullable(); // preço por pessoa
            $table->integer('min_people')->default(1);
            $table->integer('max_people')->nullable();
            $table->decimal('estimated_weight', 10, 2)->nullable(); // peso estimado em kg
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });

        // Itens do kit
        Schema::create('kit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kit_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // salvar nome em caso de produto ser deletado
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('kg');
            $table->timestamps();
        });

        // Banners
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('image');
            $table->string('link')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Bairros para entrega
        Schema::create('neighborhoods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('minimum_order', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neighborhoods');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('kit_items');
        Schema::dropIfExists('kits');
        Schema::dropIfExists('product_observations');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
