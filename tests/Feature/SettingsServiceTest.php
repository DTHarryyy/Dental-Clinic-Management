<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_add_a_service(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('settings.services.store'), [
                'name' => 'Teeth Whitening',
                'price' => 6000,
                'duration' => '60 min',
            ])
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('services', [
            'name' => 'Teeth Whitening',
            'price' => 6000,
            'duration' => '60 min',
        ]);
    }

    public function test_admin_can_edit_a_service(): void
    {
        $service = Service::create(['name' => 'Cleaning', 'price' => 1000, 'duration' => '30 min']);

        $this->actingAs($this->admin())
            ->putJson(route('settings.services.update', $service), [
                'name' => 'Deep Cleaning',
                'price' => 1800,
                'duration' => '45 min',
            ])
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Deep Cleaning',
            'price' => 1800,
            'duration' => '45 min',
        ]);
    }

    public function test_admin_can_delete_a_service(): void
    {
        $service = Service::create(['name' => 'X-Ray', 'price' => 500, 'duration' => '15 min']);

        $this->actingAs($this->admin())
            ->deleteJson(route('settings.services.destroy', $service))
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_editing_a_service_updates_the_cached_catalog(): void
    {
        $service = Service::create(['name' => 'Braces', 'price' => 3000, 'duration' => '40 min']);

        // Warm the cache, then edit — the model's booted() events must invalidate it.
        $this->assertTrue(Service::names()->contains('Braces'));

        $this->actingAs($this->admin())->putJson(route('settings.services.update', $service), [
            'name' => 'Braces Adjustment',
            'price' => 3500,
            'duration' => '40 min',
        ])->assertOk();

        $this->assertFalse(Service::names()->contains('Braces'));
        $this->assertTrue(Service::names()->contains('Braces Adjustment'));
    }

    public function test_service_validation_rejects_bad_input(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('settings.services.store'), ['name' => '', 'price' => 'not-a-number'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price']);
    }

    public function test_non_admin_cannot_manage_services(): void
    {
        $receptionist = User::factory()->create(['role' => 'receptionist', 'status' => 'active']);

        $this->actingAs($receptionist)
            ->postJson(route('settings.services.store'), ['name' => 'Sneaky', 'price' => 1])
            ->assertForbidden();

        $this->assertDatabaseMissing('services', ['name' => 'Sneaky']);
    }
}
