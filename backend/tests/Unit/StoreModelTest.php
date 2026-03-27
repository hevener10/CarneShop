<?php

namespace Tests\Unit;

use App\Models\Store;
use PHPUnit\Framework\TestCase;

class StoreModelTest extends TestCase
{
    public function test_store_without_plan_does_not_crash_on_limits(): void
    {
        $store = new Store();
        $store->setRelation('plan', null);

        $this->assertTrue($store->canAddProduct());
        $this->assertTrue($store->canAddCategory());
    }

    public function test_whatsapp_link_adds_brazilian_country_code_for_mobile_numbers(): void
    {
        $store = new Store([
            'whatsapp' => '11999999999',
        ]);

        $this->assertSame('https://wa.me/5511999999999', $store->getWhatsappLink());
    }
}
