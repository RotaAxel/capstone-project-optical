<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The clinic's stock adjustment rule, end to end through the API:
 *   - only Admin and Inventory Staff can record an adjustment;
 *   - every adjustment needs a reason;
 *   - a lost or missing item ('loss') must be recorded by the Admin (owner);
 *   - the stock is deducted and a stock movement is logged with the type,
 *     quantity, before/after stock, reference, reason and the staff member;
 *   - the movement shows up in the Stock Movements list for its month.
 */
class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $category = ProductCategory::create(['name' => 'Frames', 'type' => 'frame']);
        $this->product = Product::create([
            'sku' => 'TST-ADJ-001', 'name' => 'Test Frame', 'category_id' => $category->id,
            'cost_price' => 500, 'selling_price' => 1200, 'stock_quantity' => 20, 'reorder_point' => 5,
        ]);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_inventory_staff_records_a_damage_adjustment_with_reason_and_staff(): void
    {
        $staff = $this->actingAsRole('inventory_staff');

        $this->postJson("/api/products/{$this->product->id}/adjust", [
            'type' => 'damage', 'quantity' => 2, 'reference_number' => 'JO-0042', 'notes' => 'Frame cracked during fitting',
        ])->assertOk();

        $this->assertSame(18, $this->product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id, 'user_id' => $staff->id, 'type' => 'damage', 'quantity' => 2,
            'quantity_before' => 20, 'quantity_after' => 18,
            'reference_number' => 'JO-0042', 'notes' => 'Frame cracked during fitting',
        ]);

        // Integration: it appears in the Stock Movements list for this month, with the reason and the staff member.
        $list = $this->getJson('/api/stock-movements?type=damage&month=' . now()->format('Y-m'))->assertOk()->json('data');
        $this->assertCount(1, $list);
        $this->assertSame('Frame cracked during fitting', $list[0]['notes']);
        $this->assertSame($staff->id, $list[0]['user']['id']);

        // ...and not in another month.
        $this->assertCount(0, $this->getJson('/api/stock-movements?month=' . now()->subMonths(2)->format('Y-m'))->json('data'));
    }

    public function test_an_adjustment_without_a_reason_is_rejected(): void
    {
        $this->actingAsRole('inventory_staff');

        $this->postJson("/api/products/{$this->product->id}/adjust", ['type' => 'adjustment', 'quantity' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('notes');

        $this->assertSame(20, $this->product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_receptionists_and_optometrists_cannot_record_adjustments(): void
    {
        foreach (['receptionist', 'optometrist'] as $role) {
            $this->actingAsRole($role);
            $this->postJson("/api/products/{$this->product->id}/adjust", [
                'type' => 'damage', 'quantity' => 1, 'notes' => 'Scratched lens',
            ])->assertForbidden();
        }

        $this->assertSame(20, $this->product->fresh()->stock_quantity);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_a_lost_item_can_only_be_recorded_by_the_admin(): void
    {
        $this->actingAsRole('inventory_staff');
        $this->postJson("/api/products/{$this->product->id}/adjust", [
            'type' => 'loss', 'quantity' => 1, 'notes' => 'Missing after monthly count',
        ])->assertForbidden();
        $this->assertSame(20, $this->product->fresh()->stock_quantity);

        $this->actingAsRole('admin');
        $this->postJson("/api/products/{$this->product->id}/adjust", [
            'type' => 'loss', 'quantity' => 1, 'reference_number' => 'COUNT-2026-10', 'notes' => 'Missing after monthly count',
        ])->assertOk();
        $this->assertSame(19, $this->product->fresh()->stock_quantity);
    }
}
