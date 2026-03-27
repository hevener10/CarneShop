<?php

namespace Tests\Unit;

use App\Models\Store;
use PHPUnit\Framework\TestCase;

class StoreModelTest extends TestCase
{
    /**
     * Garante que lojas sem plano continuem operando nos limites padrão.
     */
    public function test_store_without_plan_does_not_crash_on_limits(): void
    {
        $store = new Store();
        $store->setRelation('plan', null);

        $this->assertTrue($store->canAddProduct());
        $this->assertTrue($store->canAddCategory());
    }

    /**
     * Garante que números móveis brasileiros recebam o DDI ao gerar o link do WhatsApp.
     */
    public function test_whatsapp_link_adds_brazilian_country_code_for_mobile_numbers(): void
    {
        $store = new Store([
            'whatsapp' => '11999999999',
        ]);

        $this->assertSame('https://wa.me/5511999999999', $store->getWhatsappLink());
    }
}
