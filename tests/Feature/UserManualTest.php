<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserManual;
use Tymon\JWTAuth\Facades\JWTAuth; // For generating JWT tokens

class UserManualTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $authenticatedUser; // Changed from superAdminUser and regularUser

    protected function setUp(): void
    {
        parent::setUp();

        // As per user feedback, any authenticated user is assumed to pass 'is_sass_super' for now.
        $this->authenticatedUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
        ]);
    }

    // Test for the POST route: api/admin/auth/v1/user-manual
    // (user-manual.store › UserManualController@store)

    /** @test */
    public function guest_cannot_create_user_manual()
    {
        $userManualData = UserManual::factory()->make()->toArray();

        $response = $this->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(401); // Unauthorized
    }

    // /** @test */
    // public function regular_user_cannot_create_user_manual()
    // {
    //     // This test is commented out based on the assumption that any authenticated user
    //     // will pass the 'is_sass_super' middleware. If 'is_sass_super' has specific logic
    //     // to differentiate users, this test should be re-enabled and adjusted.
    //     $nonAdminUser = User::factory()->create();
    //     $token = JWTAuth::fromUser($nonAdminUser);
    //     $userManualData = UserManual::factory()->make()->toArray();
    //
    //     $response = $this->withHeader('Authorization', 'Bearer ' . $token)
    //                      ->postJson('/api/admin/auth/v1/user-manual', $userManualData);
    //
    //     $response->assertStatus(403);
    // }

    /** @test */
    public function authenticated_user_can_create_user_manual_with_valid_data() // Renamed from super_admin_can_create...
    {
        // Assuming any authenticated user passes 'is_sass_super' as per current understanding.
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = [
            'title' => $this->faker->sentence,
            'serial_number' => $this->faker->unique()->ean8,
            'description' => $this->faker->paragraph,
            'youtube_link' => 'https://www.youtube.com/watch?v=' . $this->faker->regexify('[a-zA-Z0-9_-]{11}'),
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(201) // Created
                 ->assertJsonStructure([
                     // Define the expected JSON structure of the response
                     'data' => [
                         'id',
                         'title',
                         'serial_number',
                         'description',
                         'youtube_link',
                         'created_at',
                         'updated_at',
                     ]
                 ])
                 ->assertJson(['data' => $userManualData]);

        $this->assertDatabaseHas('user_manuals', $userManualData);
    }

    /** @test */
    public function creating_user_manual_fails_if_required_title_is_missing()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        // 'title' is required
        $userManualData = UserManual::factory()->make(['title' => null])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function creating_user_manual_fails_if_title_exceeds_max_length()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = UserManual::factory()->make(['title' => $this->faker->realText(1100)])->toArray(); // Max 1000

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function creating_user_manual_succeeds_with_null_serial_number()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = UserManual::factory()->make(['serial_number' => null])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_manuals', ['title' => $userManualData['title'], 'serial_number' => null]);
    }

    /** @test */
    public function creating_user_manual_fails_if_serial_number_is_not_integer()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = UserManual::factory()->make(['serial_number' => 'not-an-integer'])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['serial_number']);
    }

    /** @test */
    public function creating_user_manual_succeeds_with_null_description()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = UserManual::factory()->make(['description' => null])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_manuals', ['title' => $userManualData['title'], 'description' => null]);
    }

    /** @test */
    public function creating_user_manual_fails_if_description_exceeds_max_length()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        // Max 5000
        $userManualData = UserManual::factory()->make(['description' => $this->faker->realText(5100)])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['description']);
    }

    /** @test */
    public function creating_user_manual_succeeds_with_null_youtube_link()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = UserManual::factory()->make(['youtube_link' => null])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_manuals', ['title' => $userManualData['title'], 'youtube_link' => null]);
    }

    /** @test */
    public function creating_user_manual_fails_if_youtube_link_is_invalid_format()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $userManualData = UserManual::factory()->make(['youtube_link' => 'https://www.example.com/watch?v=12345'])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['youtube_link']);
    }

    /** @test */
    public function creating_user_manual_succeeds_with_valid_youtube_embed_link()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $validEmbedLink = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
        $userManualData = UserManual::factory()->make(['youtube_link' => $validEmbedLink])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_manuals', ['youtube_link' => $validEmbedLink]);
    }

    /** @test */
    public function creating_user_manual_succeeds_with_valid_youtube_embed_link_with_query_params()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $validEmbedLink = 'https://www.youtube.com/embed/dQw4w9WgXcQ?start=10';
        $userManualData = UserManual::factory()->make(['youtube_link' => $validEmbedLink])->toArray();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/admin/auth/v1/user-manual', $userManualData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('user_manuals', ['youtube_link' => $validEmbedLink]);
    }

    // #####################################################################
    // ## Read Operation Tests (Index & Show)
    // #####################################################################

    /** @test */
    public function can_get_list_of_user_manuals_from_public_route()
    {
        UserManual::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/public/user-manual');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => ['id', 'title', 'serial_number', 'description', 'youtube_link']
                     ],
                     'current_page', // Assuming pagination structure from typical Laravel API resources
                     'first_page_url',
                     'from',
                     'last_page',
                     'last_page_url',
                     'links',
                     'next_page_url',
                     'path',
                     'per_page',
                     'prev_page_url',
                     'to',
                     'total'
                 ])
                 ->assertJsonCount(3, 'data'); // Check if 3 items were created and returned
    }

    /** @test */
    public function user_manuals_list_is_paginated_correctly()
    {
        UserManual::factory()->count(15)->create();

        $response = $this->getJson('/api/admin/public/user-manual?per_page=5');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data')
                 ->assertJson(['per_page' => 5, 'total' => 15, 'current_page' => 1]);

        $response = $this->getJson('/api/admin/public/user-manual?per_page=5&page=2');
        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data')
                 ->assertJson(['per_page' => 5, 'total' => 15, 'current_page' => 2]);
    }

    /** @test */
    public function user_manuals_list_can_be_searched_by_title()
    {
        $userManual1 = UserManual::factory()->create(['title' => 'Unique Test Title Alpha']);
        $userManual2 = UserManual::factory()->create(['title' => 'Another Unique Title Beta']);
        UserManual::factory()->create(['title' => 'Something Else Entirely']);

        $response = $this->getJson('/api/admin/public/user-manual?search=Unique Test Title Alpha');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['title' => $userManual1->title])
                 ->assertJsonMissing(['title' => $userManual2->title]); // Ensure other titles not present
    }

    // It might be good to also test search by description or serial_number if the service supports it.
    // For now, assuming search primarily targets title based on typical implementations.

    /** @test */
    public function can_get_a_single_user_manual_by_id_from_public_route()
    {
        $userManual = UserManual::factory()->create();

        $response = $this->getJson("/api/admin/public/user-manual/{$userManual->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['id', 'title', 'serial_number', 'description', 'youtube_link']
                 ])
                 ->assertJson(['data' => $userManual->toArray()]);
    }

    /** @test */
    public function getting_a_non_existent_user_manual_returns_404()
    {
        $nonExistentId = 999;
        $response = $this->getJson("/api/admin/public/user-manual/{$nonExistentId}");

        $response->assertStatus(404)
                 ->assertJson(['success' => false, 'message' => 'User manual not found']);
    }

    // #####################################################################
    // ## Update Operation Tests
    // #####################################################################

    /** @test */
    public function authenticated_user_can_update_user_manual_with_valid_data()
    {
        $userManual = UserManual::factory()->create();
        $token = JWTAuth::fromUser($this->authenticatedUser);

        $updateData = [
            'title' => 'Updated Title',
            'serial_number' => 12345, // Assuming a new valid serial
            'description' => 'Updated description.',
            'youtube_link' => 'https://www.youtube.com/embed/newVideoID1',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['id', 'title', 'serial_number', 'description', 'youtube_link']
                 ])
                 ->assertJson(['data' => $updateData]); // Ensure response contains the updated data

        $this->assertDatabaseHas('user_manuals', array_merge(['id' => $userManual->id], $updateData));
    }

    /** @test */
    public function guest_cannot_update_user_manual()
    {
        $userManual = UserManual::factory()->create();
        $updateData = ['title' => 'Attempted Update by Guest'];

        $response = $this->putJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData);

        $response->assertStatus(401); // Unauthorized
    }

    /** @test */
    public function authenticated_user_can_perform_partial_update_of_user_manual()
    {
        $userManual = UserManual::factory()->create(['title' => 'Original Title']);
        $token = JWTAuth::fromUser($this->authenticatedUser);

        $updateData = ['title' => 'Partially Updated Title'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->patchJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData); // Using PATCH for partial

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'Partially Updated Title']);

        $this->assertDatabaseHas('user_manuals', ['id' => $userManual->id, 'title' => 'Partially Updated Title']);
        // Ensure other fields remain unchanged if not part of the request by fetching and checking the record.
        $updatedRecord = UserManual::find($userManual->id);
        $this->assertEquals($userManual->serial_number, $updatedRecord->serial_number);
    }

    /** @test */
    public function updating_user_manual_fails_if_title_is_provided_but_empty()
    {
        $userManual = UserManual::factory()->create();
        $token = JWTAuth::fromUser($this->authenticatedUser);
        // 'title' is 'sometimes|required' - if present, must not be empty
        $updateData = ['title' => ''];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function updating_user_manual_fails_if_title_exceeds_max_length()
    {
        $userManual = UserManual::factory()->create();
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $updateData = ['title' => $this->faker->realText(1100)]; // Max 1000

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /** @test */
    public function updating_user_manual_fails_if_serial_number_is_provided_but_not_integer()
    {
        $userManual = UserManual::factory()->create();
        $token = JWTAuth::fromUser($this->authenticatedUser);
        // 'serial_number' is 'sometimes|required|integer'
        $updateData = ['serial_number' => 'not-an-integer'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['serial_number']);
    }

    /** @test */
    public function updating_user_manual_fails_if_youtube_link_is_invalid_format()
    {
        $userManual = UserManual::factory()->create();
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $updateData = ['youtube_link' => 'invalid-youtube-link-format'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/admin/auth/v1/user-manual/{$userManual->id}", $updateData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['youtube_link']);
    }

    /** @test */
    public function updating_non_existent_user_manual_returns_404()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $nonExistentId = 9999;
        $updateData = ['title' => 'Trying to update non-existent record'];

        // The controller's update method uses findById, which should throw an exception
        // leading to a 404 or 500 depending on how UserManualService->update handles it.
        // Given the controller's catch block for update, it might be a 500 if findById fails before validation.
        // However, if findById is part of the service's update method, and the model binding fails first,
        // Laravel's default behavior for missing models in route model binding is a 404.
        // Let's assume route model binding or a similar check in the service leads to a 404.
        // If the service's findById within update throws an exception that the controller turns into 500, this needs adjustment.

        // From UserManualController: update(UpdateUserManualRequest $request, string $id)
        // Route model binding is not explicitly used for $id here, so the service's `update($id, ...)`
        // will be responsible. If `UserManualService->update` calls `findById` first and that fails,
        // `UserManualController->show`'s exception handling for `findById` (404) is a good precedent.
        // So, we'll expect the service to cause a failure that results in a 404 or similar not found.
        // The controller's generic catch (\Exception $e) might turn it into a 500 if not handled specifically.
        // Let's assume the service handles "not found" gracefully or it becomes a 500.
        // A 404 is more standard for "resource not found to update".
        // If UserManualService->update uses findOrFail or similar, it would be a 404.
        // The controller has: } catch (\Exception $e) { return response()->json([...], 500); }
        // This suggests it might be a 500 if the service's update method throws a ModelNotFoundException.
        // Let's test for 500 first, as per the controller's generic catch-all for update.
        // A more robust service might throw a custom exception caught by the controller for a 404.
        // For now, let's stick to what the controller's direct error handling implies for `update`.

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->putJson("/api/admin/auth/v1/user-manual/{$nonExistentId}", $updateData);

        // The controller code for update has a generic catch (\Exception $e) returning 500.
        // If the service's update method tries to find the model and fails (e.g. ModelNotFoundException),
        // it will likely be caught by this generic handler.
        $response->assertStatus(500);
        // A better API might return 404 here. This test might need adjustment based on actual service behavior.
        // If the service's `update` method internally uses `findOrFail` and this exception is not specifically
        // caught and re-thrown as a custom one, Laravel might convert ModelNotFoundException to 404 by default
        // *before* it hits the controller's generic catch-all if route-model binding were in play.
        // But since it's a manual $id, it depends on UserManualService.
        // Let's refine this expectation if actual behavior (when runnable) shows 404.
    }

    // #####################################################################
    // ## Delete Operation Tests
    // #####################################################################

    /** @test */
    public function authenticated_user_can_delete_user_manual()
    {
        $userManual = UserManual::factory()->create();
        $token = JWTAuth::fromUser($this->authenticatedUser);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->deleteJson("/api/admin/auth/v1/user-manual/{$userManual->id}");

        $response->assertStatus(200) // Controller returns 200 on successful delete
                 ->assertJson([
                     'success' => true,
                     'message' => 'User manual deleted successfully'
                 ]);

        $this->assertSoftDeleted('user_manuals', ['id' => $userManual->id]);
        $this->assertDatabaseHas('user_manuals', ['id' => $userManual->id]); // Record still exists
        $this->assertNotNull(UserManual::withTrashed()->find($userManual->id)->deleted_at); // Check deleted_at
    }

    /** @test */
    public function guest_cannot_delete_user_manual()
    {
        $userManual = UserManual::factory()->create();

        $response = $this->deleteJson("/api/admin/auth/v1/user-manual/{$userManual->id}");

        $response->assertStatus(401); // Unauthorized
        $this->assertNotSoftDeleted('user_manuals', ['id' => $userManual->id]);
    }

    /** @test */
    public function deleting_non_existent_user_manual_returns_error()
    {
        $token = JWTAuth::fromUser($this->authenticatedUser);
        $nonExistentId = 9999;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->deleteJson("/api/admin/auth/v1/user-manual/{$nonExistentId}");

        // Similar to update, the controller's generic catch (\Exception $e) for destroy
        // will likely turn a ModelNotFoundException from the service into a 500.
        // A 404 would be more standard. This may need adjustment based on UserManualService behavior.
        $response->assertStatus(500);
        // ->assertJson(['success' => false, 'message' => 'Failed to delete user manual']); // Or specific message
    }


    // Next: Tests for GET create/edit routes.
}
