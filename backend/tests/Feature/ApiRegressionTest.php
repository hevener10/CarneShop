<?php

namespace Tests\Feature;

use App\Http\Middleware\StoreMiddleware;
use App\Models\Category;
use App\Models\Neighborhood;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Garante que o cadastro público não aceite escalonamento para super admin.
     */
    public function test_public_registration_rejects_super_admin_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Eve',
            'email' => 'eve@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'eve@example.com',
        ]);
    }

    /**
     * Garante que o cadastro público crie dono de loja por padrão e retorne token.
     */
    public function test_public_registration_defaults_to_store_owner(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.role', User::ROLE_STORE_OWNER)
            ->assertJsonPath('data.token', fn ($token) => is_string($token) && $token !== '');

        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => User::ROLE_STORE_OWNER,
        ]);
    }

    /**
     * Garante que as rotas públicas usem o controller correto e normalizem o slug.
     */
    public function test_public_storefront_routes_resolve_correctly(): void
    {
        $store = $this->createStore(['slug' => 'minha-loja']);

        $this->getJson('/api/v1/public/stores/check-slug/Minha Loja')
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.slug', $store->slug);

        $this->getJson('/api/v1/public/stores/' . $store->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $store->slug);
    }

    /**
     * Garante que slugs inválidos sejam rejeitados após a normalização.
     */
    public function test_public_check_slug_rejects_empty_normalized_slug(): void
    {
        $this->getJson('/api/v1/public/stores/check-slug/!!!')
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    /**
     * Garante que o checkout refaça o cálculo server-side e ignore preços do cliente.
     */
    public function test_checkout_recomputes_prices_server_side(): void
    {
        $store = $this->createStore([
            'delivery_fee' => 15,
            'minimum_order' => 10,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Picanha',
            'slug' => 'picanha',
            'price' => 40,
            'is_active' => true,
            'min_gramage' => 500,
            'max_gramage' => 2000,
            'gramage_step' => 500,
        ]);

        $variation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'Bife',
            'price_adjust' => 10,
            'is_active' => true,
        ]);

        Neighborhood::create([
            'store_id' => $store->id,
            'name' => 'Centro',
            'delivery_fee' => 7,
            'minimum_order' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/public/stores/' . $store->slug . '/checkout', [
            'customer_name' => 'Cliente',
            'customer_phone' => '11999999999',
            'delivery_neighborhood' => 'Centro',
            'items' => [[
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => 2,
                'gramage' => 1000,
                'product_name' => 'Produto adulterado',
                'variation_name' => 'Fake',
                'unit_price' => 0.01,
                'subtotal' => 0.02,
            ]],
            'payment_method' => 'money',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.order.subtotal', '100.00')
            ->assertJsonPath('data.order.delivery_fee', '7.00')
            ->assertJsonPath('data.order.total', '107.00');

        $order = Order::query()->with('items')->firstOrFail();

        $this->assertSame(100.0, (float) $order->subtotal);
        $this->assertSame(107.0, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame('Picanha', $order->items[0]->product_name);
        $this->assertSame('Bife', $order->items[0]->variation_name);
        $this->assertSame(50.0, (float) $order->items[0]->unit_price);
        $this->assertSame(100.0, (float) $order->items[0]->subtotal);
    }

    /**
     * Garante que produtos sem configuração de gramatura válida sejam rejeitados no checkout.
     */
    public function test_checkout_rejects_product_without_gramage_configuration(): void
    {
        $store = $this->createStore([
            'delivery_fee' => 0,
            'minimum_order' => 0,
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Cupim',
            'slug' => 'cupim',
            'price' => 45,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/public/stores/' . $store->slug . '/checkout', [
            'customer_name' => 'Cliente',
            'customer_phone' => '11999999999',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
            'payment_method' => 'money',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.gramage');
    }

    /**
     * Garante que uma categoria de outra loja seja rejeitada ao criar produto.
     */
    public function test_product_creation_rejects_category_from_another_store(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'store-owner@example.com',
            'password' => 'secret123',
            'role' => User::ROLE_STORE_OWNER,
        ]);

        $store = $this->createStore(['user_id' => $owner->id]);
        $otherStore = $this->createStore([
            'slug' => 'other-store',
            'user_id' => User::create([
                'name' => 'Other',
                'email' => 'other-owner@example.com',
                'password' => 'secret123',
                'role' => User::ROLE_STORE_OWNER,
            ])->id,
        ]);

        $foreignCategory = Category::create([
            'store_id' => $otherStore->id,
            'name' => 'Categoria externa',
            'slug' => 'categoria-externa',
            'is_active' => true,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/stores/products', [
            'category_id' => $foreignCategory->id,
            'name' => 'Produto',
            'price' => 50,
            'min_gramage' => 500,
            'max_gramage' => 1500,
            'gramage_step' => 500,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('category_id');
    }

    /**
     * Garante que o dono da loja receba 404 claro quando a relação da loja estiver ausente.
     */
    public function test_store_owner_me_returns_404_when_store_relation_is_missing(): void
    {
        $owner = User::create([
            'name' => 'No Store',
            'email' => 'nostore@example.com',
            'password' => 'secret123',
            'role' => User::ROLE_STORE_OWNER,
        ]);

        Sanctum::actingAs($owner);

        $this->withoutMiddleware(StoreMiddleware::class)
            ->getJson('/api/v1/stores/me')
            ->assertNotFound()
            ->assertJsonPath('message', 'Nenhuma loja encontrada para este usuário.');
    }

    /**
     * Cria uma loja funcional para os testes de regressão.
     */
    private function createStore(array $overrides = []): Store
    {
        $plan = Plan::create([
            'name' => 'Plano Teste',
            'price' => 0,
            'limit_products' => 0,
            'limit_categories' => 0,
            'has_domain' => false,
            'has_api' => false,
            'is_active' => true,
            'order' => 1,
        ]);

        $userId = $overrides['user_id'] ?? User::create([
            'name' => 'Store Owner ' . uniqid(),
            'email' => uniqid('owner_', true) . '@example.com',
            'password' => 'secret123',
            'role' => User::ROLE_STORE_OWNER,
        ])->id;

        unset($overrides['user_id']);

        return Store::create(array_merge([
            'user_id' => $userId,
            'plan_id' => $plan->id,
            'name' => 'Minha Loja',
            'slug' => 'minha-loja-' . uniqid(),
            'minimum_order' => 50,
            'delivery_fee' => 0,
            'is_active' => true,
            'is_suspended' => false,
        ], $overrides));
    }
}
