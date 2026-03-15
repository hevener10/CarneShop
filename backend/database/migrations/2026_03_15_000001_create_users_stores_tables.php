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
        // Tabela de usuários (Super Admin e Donos de Loja)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['super_admin', 'store_owner', 'customer'])->default('customer');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Tabela de planos
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Basic, Premium
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('limit_products')->default(0);
            $table->integer('limit_categories')->default(0);
            $table->boolean('has_domain')->default(false);
            $table->boolean('has_api')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Tabela de lojas (tenants)
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->default(1)->constrained();
            $table->string('name');
            $table->string('slug')->unique(); // subdomain
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('primary_color', 7)->default('#FF4500');
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('minimum_order', 10, 2)->default(50);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->boolean('free_delivery')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_suspended')->default(false);
            $table->text('suspension_reason')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });

        // Tabela de assinaturas
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained();
            $table->date('starts_at');
            $table->date('expires_at')->nullable();
            $table->enum('status', ['active', 'canceled', 'suspended', 'expired'])->default('active');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('users');
    }
};
