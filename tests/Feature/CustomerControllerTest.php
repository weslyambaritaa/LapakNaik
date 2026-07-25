<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwner(): array
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko Uji', 'slug' => 'toko-uji-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        return [$owner, $business];
    }

    public function test_cannot_create_two_customers_with_the_same_phone_in_one_business(): void
    {
        [$owner, $business] = $this->makeOwner();

        Customer::create(['business_id' => $business->id, 'name' => 'Andi', 'phone' => '081234567890']);

        $response = $this->actingAs($owner)->post('/customers', [
            'name' => 'Andi Duplikat',
            'phone' => '081234567890',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertSame(1, $business->customers()->count());
    }

    public function test_the_same_phone_number_is_allowed_across_different_businesses(): void
    {
        [$ownerA, $businessA] = $this->makeOwner();
        [$ownerB, $businessB] = $this->makeOwner();

        Customer::create(['business_id' => $businessA->id, 'name' => 'Andi', 'phone' => '081234567890']);

        $response = $this->actingAs($ownerB)->post('/customers', [
            'name' => 'Andi Toko Lain',
            'phone' => '081234567890',
        ]);

        $response->assertSessionDoesntHaveErrors('phone');
        $this->assertSame(1, $businessB->customers()->count());
    }

    public function test_multiple_customers_without_a_phone_are_allowed(): void
    {
        [$owner, $business] = $this->makeOwner();

        Customer::create(['business_id' => $business->id, 'name' => 'Tanpa HP 1']);

        $response = $this->actingAs($owner)->post('/customers', [
            'name' => 'Tanpa HP 2',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(2, $business->customers()->count());
    }
}
