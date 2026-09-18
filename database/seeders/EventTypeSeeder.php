<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Yeni Ürün Satışı', 'slug' => 'new-product-sale'],
            ['name' => 'Ödeme Gecikmesi', 'slug' => 'payment-delay'],
            ['name' => 'Tahsilat Problemi', 'slug' => 'collection-problem'],
            ['name' => 'Sözleşme İhlali', 'slug' => 'contract-breach'],
            ['name' => 'Müşteri Şikâyeti', 'slug' => 'customer-complaint'],
            ['name' => 'İşçi / İşveren Uyuşmazlığı', 'slug' => 'employment-dispute'],
            ['name' => 'Tedarikçi Uyuşmazlığı', 'slug' => 'supplier-dispute'],
            ['name' => 'Diğer', 'slug' => 'other'],
        ] as $eventType) {
            EventType::query()->updateOrCreate(['slug' => $eventType['slug']], $eventType);
        }
    }
}
