<?php

namespace Tests\Feature\Storefront;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLookupTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusiness(string $suffix = ''): Business
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Online'.$suffix, 'slug' => 'toko-online-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return $business;
    }

    public function test_returns_the_customer_name_for_a_registered_phone(): void
    {
        $business = $this->makeBusiness();
        Customer::create(['business_id' => $business->id, 'name' => 'Dewi', 'phone' => '081234567890']);

        $response = $this->postJson(route('storefront.customer-lookup', $business->slug), [
            'phone' => '081234567890',
        ]);

        $response->assertOk()->assertJson(['name' => 'Dewi']);
    }

    public function test_returns_a_null_name_for_an_unregistered_phone(): void
    {
        $business = $this->makeBusiness();

        $response = $this->postJson(route('storefront.customer-lookup', $business->slug), [
            'phone' => '089999999999',
        ]);

        $response->assertOk()->assertJson(['name' => null]);
    }

    public function test_does_not_leak_a_customer_registered_under_another_business(): void
    {
        $businessA = $this->makeBusiness('-a');
        $businessB = $this->makeBusiness('-b');

        Customer::create(['business_id' => $businessA->id, 'name' => 'Dewi', 'phone' => '081234567890']);

        $response = $this->postJson(route('storefront.customer-lookup', $businessB->slug), [
            'phone' => '081234567890',
        ]);

        $response->assertOk()->assertJson(['name' => null]);
    }

    public function test_response_never_exposes_more_than_the_name(): void
    {
        $business = $this->makeBusiness();
        Customer::create(['business_id' => $business->id, 'name' => 'Dewi', 'phone' => '081234567890', 'loyalty_points' => 42]);

        $response = $this->postJson(route('storefront.customer-lookup', $business->slug), [
            'phone' => '081234567890',
        ]);

        $response->assertExactJson(['name' => 'Dewi']);
    }
}
