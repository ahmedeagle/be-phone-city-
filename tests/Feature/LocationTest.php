<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test: User can fetch their locations list
     */
    public function test_user_can_fetch_their_locations_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Location::factory()->count(3)->create(['user_id' => $user->id]);
        Location::factory()->count(2)->create(); // Other user's locations

        $response = $this->getJson('/api/v1/locations');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'country',
                        'city',
                        'street_address',
                        'phone',
                        'email',
                        'label',
                        'created_at',
                    ],
                ],
                'pagination',
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data); // Only user's locations
    }

    /**
     * Test: User can fetch paginated locations
     */
    public function test_user_can_fetch_paginated_locations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Location::factory()->count(20)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/locations?per_page=10');

        $response->assertStatus(200);
        $data = $response->json('data');
        $pagination = $response->json('pagination');

        $this->assertCount(10, $data);
        $this->assertEquals(20, $pagination['total']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(2, $pagination['last_page']);
    }

    /**
     * Test: User can fetch single location
     */
    public function test_user_can_fetch_single_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $location->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ]);
    }

    /**
     * Test: User cannot fetch another user's location
     */
    public function test_user_cannot_fetch_another_users_location(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(404);
    }

    /**
     * Test: User can create a new location
     */
    public function test_user_can_create_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $locationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street_address' => '123 Main Street, Downtown',
            'phone' => '01234567890',
            'email' => 'john@example.com',
            'label' => 'Home',
        ];

        $response = $this->postJson('/api/v1/locations', $locationData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'data' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'country' => 'Egypt',
                    'city' => 'Cairo',
                    'street_address' => '123 Main Street, Downtown',
                    'phone' => '01234567890',
                    'email' => 'john@example.com',
                    'label' => 'Home',
                ],
            ]);

        $this->assertDatabaseHas('locations', [
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'Egypt',
            'city' => 'Cairo',
        ]);
    }

    /**
     * Test: User can create location without label (optional field)
     */
    public function test_user_can_create_location_without_label(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $locationData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'country' => 'Egypt',
            'city' => 'Alexandria',
            'street_address' => '456 Beach Road',
            'phone' => '01234567891',
            'email' => 'jane@example.com',
        ];

        $response = $this->postJson('/api/v1/locations', $locationData);

        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'data' => [
                    'label' => null,
                ],
            ]);

        $this->assertDatabaseHas('locations', [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'label' => null,
        ]);
    }

    /**
     * Test: Location creation requires all mandatory fields
     */
    public function test_location_creation_requires_mandatory_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/locations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'country',
                'city',
                'street_address',
                'phone',
                'email',
            ]);
    }

    /**
     * Test: Location creation validates email format
     */
    public function test_location_creation_validates_email_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $locationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street_address' => '123 Main Street',
            'phone' => '01234567890',
            'email' => 'invalid-email',
        ];

        $response = $this->postJson('/api/v1/locations', $locationData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Location creation validates string length
     */
    public function test_location_creation_validates_string_length(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $locationData = [
            'first_name' => str_repeat('a', 256), // Exceeds max:255
            'last_name' => 'Doe',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street_address' => '123 Main Street',
            'phone' => '01234567890',
            'email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/v1/locations', $locationData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    }

    /**
     * Test: User can update their location
     */
    public function test_user_can_update_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'city' => 'Cairo',
        ]);

        $updateData = [
            'first_name' => 'Jane',
            'city' => 'Alexandria',
            'label' => 'Work',
        ];

        $response = $this->putJson("/api/v1/locations/{$location->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $location->id,
                    'first_name' => 'Jane',
                    'city' => 'Alexandria',
                    'label' => 'Work',
                ],
            ]);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'first_name' => 'Jane',
            'city' => 'Alexandria',
            'label' => 'Work',
        ]);
    }

    /**
     * Test: User can partially update location
     */
    public function test_user_can_partially_update_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'city' => 'Cairo',
        ]);

        $updateData = [
            'city' => 'Alexandria',
        ];

        $response = $this->putJson("/api/v1/locations/{$location->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'first_name' => 'John', // Unchanged
            'last_name' => 'Doe', // Unchanged
            'city' => 'Alexandria', // Updated
        ]);
    }

    /**
     * Test: User cannot update another user's location
     */
    public function test_user_cannot_update_another_users_location(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create(['user_id' => $otherUser->id]);

        $updateData = [
            'city' => 'Hacked City',
        ];

        $response = $this->putJson("/api/v1/locations/{$location->id}", $updateData);

        $response->assertStatus(404);
    }

    /**
     * Test: Location update validates email format
     */
    public function test_location_update_validates_email_format(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'email' => 'invalid-email',
        ];

        $response = $this->putJson("/api/v1/locations/{$location->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: User can delete their location
     */
    public function test_user_can_delete_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseMissing('locations', [
            'id' => $location->id,
        ]);
    }

    /**
     * Test: User cannot delete another user's location
     */
    public function test_user_cannot_delete_another_users_location(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
        ]);
    }

    /**
     * Test: Unauthenticated user cannot access locations
     */
    public function test_unauthenticated_user_cannot_access_locations(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertStatus(401);
    }

    /**
     * Test: Unauthenticated user cannot create location
     */
    public function test_unauthenticated_user_cannot_create_location(): void
    {
        $locationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'street_address' => '123 Main Street',
            'phone' => '01234567890',
            'email' => 'john@example.com',
        ];

        $response = $this->postJson('/api/v1/locations', $locationData);

        $response->assertStatus(401);
    }

    /**
     * Test: Unauthenticated user cannot update location
     */
    public function test_unauthenticated_user_cannot_update_location(): void
    {
        $location = Location::factory()->create();

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'city' => 'New City',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Unauthenticated user cannot delete location
     */
    public function test_unauthenticated_user_cannot_delete_location(): void
    {
        $location = Location::factory()->create();

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response->assertStatus(401);
    }

    /**
     * Test: Locations are ordered by created_at desc
     */
    public function test_locations_are_ordered_by_created_at_desc(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location1 = Location::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'First',
        ]);
        sleep(1); // Ensure different timestamps
        $location2 = Location::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Second',
        ]);

        $response = $this->getJson('/api/v1/locations');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals($location2->id, $data[0]['id']);
        $this->assertEquals($location1->id, $data[1]['id']);
    }

    /**
     * Test: User can have multiple locations
     */
    public function test_user_can_have_multiple_locations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Location::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/v1/locations');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(5, $data);
    }

    /**
     * Test: Location update with empty label sets it to null
     */
    public function test_location_update_with_empty_label_sets_to_null(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $location = Location::factory()->create([
            'user_id' => $user->id,
            'label' => 'Home',
        ]);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'label' => null,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'label' => null,
        ]);
    }
}

