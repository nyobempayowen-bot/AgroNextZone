<?php

namespace Tests\Feature;

use Tests\TestCase;

class MessagingTest extends TestCase
{
    public function test_product_detail_has_discussion_link_with_producer(): void
    {
        $response = $this->get('/produit/cacao-fermente-qualite-superieure');

        $response->assertStatus(200)
            ->assertSee('Discuter');
    }

    public function test_producer_message_page_loads_and_accepts_message(): void
    {
        $response = $this->post('/discussion/1', [
            'message' => 'Bonjour, je veux commander du cacao fermenté.',
        ]);

        $response->assertRedirect('/discussion/1');
        $this->assertNotEmpty(session('chat.1'));
    }
}
