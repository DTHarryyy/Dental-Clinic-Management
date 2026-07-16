<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DentistDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_dentists_are_offered(): void
    {
        $active = User::factory()->dentist()->create(['name' => 'Dr. Active', 'status' => 'active']);
        User::factory()->dentist()->create(['name' => 'Dr. Retired', 'status' => 'inactive']);
        User::factory()->admin()->create(['name' => 'Clinic Admin']);
        User::factory()->create(['name' => 'Front Desk', 'role' => 'receptionist', 'status' => 'active']);

        $this->assertSame(['Dr. Active'], User::cachedDentists()->pluck('name')->all());
        $this->assertTrue(User::cachedDentists()->contains('id', $active->id));
    }

    public function test_deactivating_a_dentist_drops_them_from_the_dropdown(): void
    {
        $dentist = User::factory()->dentist()->create(['name' => 'Dr. Leaving', 'status' => 'active']);

        $this->assertSame(['Dr. Leaving'], User::cachedDentists()->pluck('name')->all());

        // The cache is rememberForever, so this only works because saving a user evicts it.
        $dentist->update(['status' => 'inactive']);

        $this->assertSame([], User::cachedDentists()->pluck('name')->all());
    }

    public function test_a_reactivated_dentist_comes_back(): void
    {
        $dentist = User::factory()->dentist()->create(['name' => 'Dr. Returning', 'status' => 'inactive']);

        $this->assertSame([], User::cachedDentists()->pluck('name')->all());

        $dentist->update(['status' => 'active']);

        $this->assertSame(['Dr. Returning'], User::cachedDentists()->pluck('name')->all());
    }

    public function test_an_inactive_dentist_still_owns_their_past_work(): void
    {
        $dentist = User::factory()->dentist()->create(['name' => 'Dr. Retired', 'status' => 'inactive']);
        $record = \App\Models\DentalRecord::factory()->create(['dentist_id' => $dentist->id]);

        // Filtering the dropdown must not orphan history.
        $this->assertSame('Dr. Retired', $record->fresh()->dentist->name);
    }

    public function test_the_appointment_form_lists_only_active_dentists(): void
    {
        User::factory()->dentist()->create(['name' => 'Dr. Bookable', 'status' => 'active']);
        User::factory()->dentist()->create(['name' => 'Dr. Gone', 'status' => 'inactive']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Dr. Bookable')
            ->assertDontSee('Dr. Gone');
    }
}
