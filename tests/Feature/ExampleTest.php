<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_la_raiz_redirige_a_login_para_invitados(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
