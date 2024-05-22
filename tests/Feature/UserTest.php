<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testExample(): void
    {
        // $response = $this->get('/auto-update-dashboard');
        // or
        $response = $this->get(route('dashboard'));


        $response->assertStatus(200);
    }
}
