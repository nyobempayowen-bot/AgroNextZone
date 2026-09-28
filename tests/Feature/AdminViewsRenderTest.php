<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\AdminService;
use Tests\TestCase;

class AdminViewsRenderTest extends TestCase
{
    public function test_admin_views_render_without_errors(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $s = app(AdminService::class);

        $cases = [
            'admin.verifications' => ['pending' => $s->pendingProducers()],
            'admin.products' => ['products' => $s->products(''), 'search' => ''],
            'admin.orders' => ['orders' => $s->orders('all'), 'statuses' => Order::STATUSES, 'selectedStatus' => 'all'],
            'admin.reviews' => ['reviews' => $s->reviews('all'), 'selectedFilter' => 'all'],
            'admin.prices' => ['prices' => $s->priceMarket()],
            'admin.settings' => ['categories' => $s->categories()],
        ];

        foreach ($cases as $view => $data) {
            $rendered = view($view, $data)->render();
            $this->assertNotEmpty($rendered, "La vue [{$view}] a rendu une sortie vide.");
        }
    }
}
