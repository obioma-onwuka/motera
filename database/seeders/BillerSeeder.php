<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billers = [
            [
                'name' => 'MTN Airtime',
                'category' => \App\Enums\BillCategory::AIRTIME,
                'logo_url' => 'https://example.com/mtn.png',
            ],
            [
                'name' => 'Airtel Data',
                'category' => \App\Enums\BillCategory::DATA,
                'logo_url' => 'https://example.com/airtel.png',
            ],
            [
                'name' => 'IKEDC Electricity',
                'category' => \App\Enums\BillCategory::ELECTRICITY,
                'logo_url' => 'https://example.com/ikedc.png',
            ],
            [
                'name' => 'DSTV Subscription',
                'category' => \App\Enums\BillCategory::CABLE_TV,
                'logo_url' => 'https://example.com/dstv.png',
            ],
        ];

        foreach ($billers as $biller) {
            \App\Models\Biller::create($biller);
        }
    }
}
