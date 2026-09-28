<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    public function test_marketplace_page_loads_and_lists_products(): void
    {
        $response = $this->get('/marketplace');

        $response->assertStatus(200)
            ->assertSee('Marketplace')
            ->assertSee('Cacao fermenté')
            ->assertSee('Plantain doux');
    }
}
