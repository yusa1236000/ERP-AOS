<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemCurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currencies = [
            [
                'code' => 'INR',
                'name' => 'Indian Rupee',
                'symbol' => '₹',
                'decimal_places' => 2,
                'countries' => json_encode(['IN']),
                'is_active' => true,
                'sort_order' => 1,
                'is_base_currency' => false,
            ],
            [
                'code' => 'VND',
                'name' => 'Vietnamese Dong',
                'symbol' => '₫',
                'decimal_places' => 0,
                'countries' => json_encode(['VN']),
                'is_active' => true,
                'sort_order' => 2,
                'is_base_currency' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            DB::table('system_currencies')->updateOrInsert(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
