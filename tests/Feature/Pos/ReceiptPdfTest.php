<?php

namespace Tests\Feature\Pos;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_a_receipt_as_pdf(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_OWNER]);
        $business = Business::create(['owner_id' => $owner->id, 'name' => 'Toko PDF', 'slug' => 'toko-pdf-'.$owner->id]);
        $owner->update(['business_id' => $business->id]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Es Jeruk',
            'price' => 6000,
            'stock' => 10,
            'unit' => 'gelas',
        ]);

        $this->actingAs($owner)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $transaction = $business->transactions()->first();

        $response = $this->actingAs($owner)->get(route('pos.receipt.pdf', $transaction));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_download_receipt_pdf_for_another_businesss_transaction(): void
    {
        $ownerA = User::factory()->create(['role' => User::ROLE_OWNER]);
        $businessA = Business::create(['owner_id' => $ownerA->id, 'name' => 'Toko A', 'slug' => 'toko-a-'.$ownerA->id]);
        $ownerA->update(['business_id' => $businessA->id]);

        $ownerB = User::factory()->create(['role' => User::ROLE_OWNER]);
        $businessB = Business::create(['owner_id' => $ownerB->id, 'name' => 'Toko B', 'slug' => 'toko-b-'.$ownerB->id]);
        $ownerB->update(['business_id' => $businessB->id]);

        $product = Product::create([
            'business_id' => $businessA->id,
            'name' => 'Teh Tawar',
            'price' => 4000,
            'stock' => 10,
            'unit' => 'gelas',
        ]);

        $this->actingAs($ownerA)->post('/pos', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'discount' => 0,
            'payment_method' => 'cash',
        ]);

        $transaction = $businessA->transactions()->first();

        $response = $this->actingAs($ownerB)->get(route('pos.receipt.pdf', $transaction));

        $response->assertForbidden();
    }
}
