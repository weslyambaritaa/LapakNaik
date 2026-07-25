<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankAccountControllerTest extends TestCase
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

    public function test_owner_can_add_a_bank_account(): void
    {
        [$owner, $business] = $this->makeOwner();

        $response = $this->actingAs($owner)->post(route('bank-accounts.store'), [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Bu Sari',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $business->bankAccounts()->count());
    }

    public function test_owner_can_add_multiple_bank_accounts(): void
    {
        [$owner, $business] = $this->makeOwner();

        $this->actingAs($owner)->post(route('bank-accounts.store'), [
            'bank_name' => 'BCA', 'account_number' => '111', 'account_holder_name' => 'Bu Sari',
        ]);
        $this->actingAs($owner)->post(route('bank-accounts.store'), [
            'bank_name' => 'Mandiri', 'account_number' => '222', 'account_holder_name' => 'Bu Sari',
        ]);

        $this->assertSame(2, $business->bankAccounts()->count());
    }

    public function test_owner_can_delete_a_bank_account(): void
    {
        [$owner, $business] = $this->makeOwner();
        $account = $business->bankAccounts()->create([
            'bank_name' => 'BCA', 'account_number' => '111', 'account_holder_name' => 'Bu Sari',
        ]);

        $this->actingAs($owner)->delete(route('bank-accounts.destroy', $account))->assertRedirect();

        $this->assertSame(0, $business->bankAccounts()->count());
    }

    public function test_cashier_cannot_add_a_bank_account(): void
    {
        [, $business] = $this->makeOwner();
        $cashier = $this->makeCashier($business);

        $this->actingAs($cashier)->post(route('bank-accounts.store'), [
            'bank_name' => 'BCA', 'account_number' => '111', 'account_holder_name' => 'Bu Sari',
        ])->assertForbidden();
    }

    public function test_owner_cannot_delete_another_businesss_bank_account(): void
    {
        [$owner] = $this->makeOwner();
        [, $otherBusiness] = $this->makeOwner();
        $account = $otherBusiness->bankAccounts()->create([
            'bank_name' => 'BCA', 'account_number' => '111', 'account_holder_name' => 'Bu Sari',
        ]);

        $this->actingAs($owner)->delete(route('bank-accounts.destroy', $account))->assertForbidden();
        $this->assertNotNull(BankAccount::find($account->id));
    }
}
