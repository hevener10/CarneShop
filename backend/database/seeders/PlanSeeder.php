<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Grátis',
                'description' => 'Perfeito para começar. Ideal para pequenos açougues.',
                'price' => 0,
                'limit_products' => 50,
                'limit_categories' => 3,
                'has_domain' => false,
                'has_api' => false,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Básico',
                'description' => 'Para negócios em crescimento. Domínio próprio.',
                'price' => 49.90,
                'limit_products' => 200,
                'limit_categories' => 0,
                'has_domain' => true,
                'has_api' => false,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Premium',
                'description' => 'Para negócios estabelecidos. Tudo ilimitado + API.',
                'price' => 99.90,
                'limit_products' => 0,
                'limit_categories' => 0,
                'has_domain' => true,
                'has_api' => true,
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
