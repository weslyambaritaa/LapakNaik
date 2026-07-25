<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwner(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Uji', 'slug' => 'toko-uji-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return [$owner, $business];
    }

    private function makeCashier(Business $business): User
    {
        return User::factory()->create(['role' => User::ROLE_KASIR, 'business_id' => $business->id]);
    }

    public function test_owner_can_upload_a_qris_image(): void
    {
        Storage::fake('public');
        [$owner, $business] = $this->makeOwner();

        $response = $this->actingAs($owner)->post(route('settings.payment.update'), [
            'qris_image' => UploadedFile::fake()->image('qris.png'),
        ]);

        $response->assertRedirect();
        $this->assertNotNull($business->fresh()->qris_image_path);
        Storage::disk('public')->assertExists($business->fresh()->qris_image_path);
    }

    public function test_uploading_a_new_image_deletes_the_previous_one(): void
    {
        Storage::fake('public');
        [$owner, $business] = $this->makeOwner();

        $this->actingAs($owner)->post(route('settings.payment.update'), [
            'qris_image' => UploadedFile::fake()->image('qris-1.png'),
        ]);
        $firstPath = $business->fresh()->qris_image_path;

        $this->actingAs($owner)->post(route('settings.payment.update'), [
            'qris_image' => UploadedFile::fake()->image('qris-2.png'),
        ]);

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($business->fresh()->qris_image_path);
    }

    public function test_owner_can_remove_the_qris_image(): void
    {
        Storage::fake('public');
        [$owner, $business] = $this->makeOwner();

        $this->actingAs($owner)->post(route('settings.payment.update'), [
            'qris_image' => UploadedFile::fake()->image('qris.png'),
        ]);
        $path = $business->fresh()->qris_image_path;

        $this->actingAs($owner)->delete(route('settings.payment.destroy'));

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($business->fresh()->qris_image_path);
    }

    public function test_cashier_cannot_access_payment_settings(): void
    {
        [, $business] = $this->makeOwner();
        $cashier = $this->makeCashier($business);

        $this->actingAs($cashier)->get(route('settings.payment.edit'))->assertForbidden();
    }
}
