<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Settings was converted from a full page into a popover overlay: each tab keeps its
 * own route (for direct links and permission middleware), but a request carrying
 * X-Requested-With: XMLHttpRequest — the same header billing-details.js already
 * uses for its "fetch a partial into a dialog" pattern — now gets just the inner
 * partial instead of the full page, so the popover can swap it into the open modal
 * without a real navigation that would replace the sidebar/topbar underneath it.
 */
class SettingsPopoverTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_a_direct_visit_renders_the_host_shell_that_auto_opens_the_dialog(): void
    {
        // Settings has no standalone page: a hard navigation returns the ordinary app
        // shell plus a marker telling settings-popover.js which tab to open onto.
        $this->actingAs($this->admin())
            ->get(route('settings.clinic'))
            ->assertOk()
            ->assertSee('data-app-sidebar', false)
            ->assertSee('data-settings-autoopen="'.route('settings.clinic').'"', false)
            ->assertDontSee('Clinic Information');
    }

    public function test_an_ajax_request_renders_only_the_tab_content(): void
    {
        // The rail lives in the dialog shell, so a tab fetch must carry content only —
        // no app chrome and no nav (otherwise a tab switch would duplicate the rail).
        $this->actingAs($this->admin())
            ->get(route('settings.clinic'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Clinic Information')
            ->assertDontSee('data-app-sidebar', false)
            ->assertDontSee('settings-nav-item', false)
            ->assertDontSee('Payment Channels');
    }

    public function test_the_dialog_shell_carries_the_rail_on_every_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-settings-dialog', false)
            ->assertSee('settings-dialog-rail', false)
            ->assertSee('data-settings-popover-body', false)
            ->assertSee('Payment Channels');
    }

    public function test_the_services_tab_partial_still_includes_its_nested_dialogs(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.services'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('data-app-sidebar', false)
            ->assertSee('open-service-create', false)
            ->assertSee('open-service-delete', false);
    }

    public function test_the_closures_tab_partial_renders_without_the_shell(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.closures'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('data-app-sidebar', false)
            ->assertSee('Add closure');
    }

    public function test_a_denied_staff_user_is_rejected_identically_for_both_render_modes(): void
    {
        // Only Admin has Permission::SettingsView (see PermissionMatrix) — a dentist
        // is staff (passes active.staff) but is denied by the can:settings.view gate.
        $dentist = User::factory()->dentist()->create(['status' => 'active']);

        $this->actingAs($dentist)->get(route('settings.clinic'))->assertForbidden();
        $this->actingAs($dentist)
            ->get(route('settings.clinic'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertForbidden();
    }

    public function test_a_saved_flash_message_appears_inline_in_the_ajax_partial_and_is_consumed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->putJson(route('settings.clinic.update'), [
            'clinic_name' => 'Updated Clinic Name',
        ])->assertOk();

        $response = $this->actingAs($admin)
            ->get(route('settings.clinic'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
        $response->assertSee('Clinic information saved.');

        // The flash is pulled (read + cleared) on first render, so a second ajax
        // fetch of the same tab must not show it again.
        $this->actingAs($admin)
            ->get(route('settings.clinic'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertDontSee('Clinic information saved.');
    }
}
