<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_can_have_many_clients(): void
    {
        // Create an artist and multiple clients
        $artist = User::factory()->create(['role' => 'artist']);
        $clients = User::factory(3)->create(['role' => 'client']);

        // Attach all clients to the artist
        $artist->clients()->attach($clients);

        // Assert the relationships exist
        $this->assertCount(3, $artist->clients);
        foreach ($clients as $client) {
            $this->assertTrue($artist->hasAccessToClient($client));
        }
    }

    public function test_client_can_have_many_artists(): void
    {
        // Create a client and multiple artists
        $client = User::factory()->create(['role' => 'client']);
        $artists = User::factory(3)->create(['role' => 'artist']);

        // Attach all artists to the client
        foreach ($artists as $artist) {
            $artist->clients()->attach($client);
        }

        // Assert the relationships exist
        $this->assertCount(3, $client->artists);
    }

    public function test_has_access_to_client_returns_false_for_unrelated_client(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $unrelatedClient = User::factory()->create(['role' => 'client']);

        $this->assertFalse($artist->hasAccessToClient($unrelatedClient));
    }

    public function test_has_access_to_client_returns_true_for_related_client(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);

        $artist->clients()->attach($client);

        $this->assertTrue($artist->hasAccessToClient($client));
    }
}
