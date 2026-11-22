<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\Download;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Automated tests for the most important business rules.
 * Run them with:  php artisan test
 * (They use a temporary in-memory database, so your real data is never touched.)
 */
class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    // ---------- small helpers ----------

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    private function makeSellerWithArtwork(string $status = Artwork::APPROVED): array
    {
        $seller = User::factory()->create();
        $store = Store::create(['user_id' => $seller->id, 'name' => 'Shop ' . $seller->id, 'slug' => 'shop-' . $seller->id]);
        Storage::disk('local')->put('artworks/art.png', 'ORIGINAL-FILE');

        $artwork = $store->artworks()->create([
            'title' => 'Sunset', 'slug' => 'sunset-' . $seller->id, 'price' => 100,
            'preview_path' => 'previews/p.jpg', 'file_path' => 'artworks/art.png',
            'file_name' => 'sunset.png', 'status' => $status,
        ]);

        return [$seller, $artwork];
    }

    /** Buyer adds artwork to cart, checks out and pays with the GCash TEST simulator. */
    private function buy(User $buyer, Artwork $artwork, string $action = 'authorize'): Order
    {
        $this->actingAs($buyer)->post(route('cart.add', $artwork));
        $this->actingAs($buyer)->post(route('checkout'));
        $order = $buyer->orders()->latest('id')->first();

        $this->actingAs($buyer)->post(route('orders.pay', $order))
            ->assertRedirect(route('payments.gcash.test', $order));
        $this->actingAs($buyer)->post(route('payments.gcash.testPay', $order), [
            'gcash_number' => '0917 123 4567', 'action' => $action,
        ]);

        return $order->fresh();
    }

    /** Creates an unpaid (pending) order, used by the live GCash tests. */
    private function pendingOrder(User $buyer, Artwork $artwork): Order
    {
        $this->actingAs($buyer)->post(route('cart.add', $artwork));
        $this->actingAs($buyer)->post(route('checkout'));

        return $buyer->orders()->latest('id')->first();
    }

    // ---------- tests ----------

    public function test_registration_creates_user_and_notifies_admin(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/register', [
            'name' => 'New Person', 'email' => 'new@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertTrue($admin->notifications()->where('type', 'new_user')->exists());
    }

    public function test_only_admins_can_open_admin_pages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($this->makeAdmin())->get('/admin')->assertOk();
    }

    public function test_new_artwork_is_pending_and_hidden_until_admin_approves(): void
    {
        $admin = $this->makeAdmin();
        [$seller] = $this->makeSellerWithArtwork();

        $this->actingAs($seller)->post(route('seller.artworks.store'), [
            'title' => 'Brand New', 'price' => 50,
            'preview' => UploadedFile::fake()->image('preview.jpg'),
            'file' => UploadedFile::fake()->image('original.png'),
        ])->assertRedirect(route('seller.artworks.index'));

        $artwork = Artwork::where('title', 'Brand New')->first();
        $this->assertEquals(Artwork::PENDING, $artwork->status);
        Storage::disk('local')->assertExists($artwork->file_path); // original stored PRIVATELY
        Auth::forgetGuards(); // check the page as a GUEST visitor
        $this->get(route('artworks.show', $artwork->slug))->assertNotFound();

        $this->actingAs($admin)->post(route('admin.artworks.approve', $artwork));
        $this->assertEquals(Artwork::APPROVED, $artwork->fresh()->status);
        $this->assertTrue($seller->notifications()->where('type', 'artwork_approved')->exists());
        Auth::forgetGuards();
        $this->get(route('artworks.show', $artwork->slug))->assertOk();
    }

    public function test_admin_rejection_requires_reason_and_notifies_seller(): void
    {
        $admin = $this->makeAdmin();
        [$seller, $artwork] = $this->makeSellerWithArtwork(Artwork::PENDING);

        $this->actingAs($admin)->post(route('admin.artworks.reject', $artwork), [])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.artworks.reject', $artwork), ['reason' => 'Blurry']);

        $this->assertEquals(Artwork::REJECTED, $artwork->fresh()->status);
        $this->assertDatabaseHas('moderation_logs', ['artwork_id' => $artwork->id, 'action' => 'rejected', 'reason' => 'Blurry']);
        $this->assertTrue($seller->notifications()->where('type', 'artwork_rejected')->exists());
    }

    public function test_seller_cannot_edit_someone_elses_artwork(): void
    {
        [, $artwork] = $this->makeSellerWithArtwork();
        [$otherSeller] = $this->makeSellerWithArtwork();

        $this->actingAs($otherSeller)->get(route('seller.artworks.edit', $artwork))->assertForbidden();
    }

    public function test_seller_cannot_buy_own_artwork(): void
    {
        [$seller, $artwork] = $this->makeSellerWithArtwork();

        $this->actingAs($seller)->post(route('cart.add', $artwork));
        $this->assertEquals(0, $seller->getCart()->items()->count());
    }

    public function test_declined_payment_does_not_unlock_download(): void
    {
        [, $artwork] = $this->makeSellerWithArtwork();
        $buyer = User::factory()->create();

        $order = $this->buy($buyer, $artwork, 'decline');
        $this->assertEquals('failed', $order->payments()->first()->status);

        $this->assertEquals(Order::PENDING, $order->status);
        $this->actingAs($buyer)->post(route('purchases.download', $order->items->first()));
        $this->assertEquals(0, Download::count());
    }

    public function test_full_purchase_allows_exactly_one_download(): void
    {
        [$seller, $artwork] = $this->makeSellerWithArtwork();
        $buyer = User::factory()->create();

        $order = $this->buy($buyer, $artwork);
        $this->assertEquals(Order::PAID, $order->status);
        $item = $order->items->first();

        // First download works and returns the private file
        $this->actingAs($buyer)->post(route('purchases.download', $item))
            ->assertOk()
            ->assertDownload('sunset.png');
        $this->assertDatabaseHas('downloads', ['order_item_id' => $item->id, 'user_id' => $buyer->id]);
        $this->assertTrue($seller->notifications()->where('type', 'artwork_downloaded')->exists());

        // Second download is refused
        $this->actingAs($buyer)->post(route('purchases.download', $item))->assertRedirect();
        $this->assertEquals(1, Download::count());
    }

    public function test_other_users_cannot_download_your_purchase(): void
    {
        [, $artwork] = $this->makeSellerWithArtwork();
        $buyer = User::factory()->create();
        $order = $this->buy($buyer, $artwork);

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->post(route('purchases.download', $order->items->first()))->assertForbidden();
    }

    public function test_gcash_test_payment_saves_masked_number(): void
    {
        [, $artwork] = $this->makeSellerWithArtwork();
        $order = $this->buy(User::factory()->create(), $artwork);

        $payment = $order->payments()->first();
        $this->assertEquals('success', $payment->status);
        $this->assertEquals('0917****567', $payment->account_number);
        $this->assertEquals('test', $payment->mode);
    }

    public function test_live_gcash_redirects_to_paymongo_and_verifies_before_unlocking(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_fake']);
        // Fake PayMongo's answers – no real internet call happens in tests.
        Http::fake([
            'api.paymongo.com/v1/checkout_sessions' => Http::response(['data' => [
                'id' => 'cs_123', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_123'],
            ]]),
            'api.paymongo.com/v1/checkout_sessions/cs_123' => Http::sequence()
                ->push(['data' => ['attributes' => ['payments' => [], 'payment_intent' => ['attributes' => ['status' => 'awaiting_payment_method']]]]])
                ->push(['data' => ['attributes' => ['payments' => [['attributes' => ['status' => 'paid']]]]]]),
        ]);

        [, $artwork] = $this->makeSellerWithArtwork();
        $buyer = User::factory()->create();
        $order = $this->pendingOrder($buyer, $artwork);

        // 1. "Pay with GCash" sends the buyer to PayMongo's GCash page
        $this->actingAs($buyer)->post(route('orders.pay', $order))
            ->assertRedirect('https://checkout.paymongo.com/cs_123');
        $payment = $order->payments()->first();
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('cs_123', $payment->provider_reference);

        // 2. Back on our site but PayMongo says NOT paid yet -> order stays pending
        $this->actingAs($buyer)->get(route('payments.gcash.return', $payment));
        $this->assertEquals(Order::PENDING, $order->fresh()->status);

        // 3. "Check payment status" again, now PayMongo says paid -> order paid
        $this->actingAs($buyer)->get(route('payments.gcash.return', $payment))
            ->assertRedirect(route('purchases.index'));
        $this->assertEquals(Order::PAID, $order->fresh()->status);
        $this->assertEquals('success', $payment->fresh()->status);

        // The simulator is switched off in live mode
        $this->actingAs($buyer)->get(route('payments.gcash.test', $order))->assertNotFound();
    }

    public function test_live_gcash_cancel_marks_payment_failed(): void
    {
        config(['services.paymongo.secret_key' => 'sk_test_fake']);
        Http::fake(['api.paymongo.com/*' => Http::response(['data' => [
            'id' => 'cs_999', 'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/cs_999'],
        ]])]);

        [, $artwork] = $this->makeSellerWithArtwork();
        $buyer = User::factory()->create();
        $order = $this->pendingOrder($buyer, $artwork);
        $this->actingAs($buyer)->post(route('orders.pay', $order));
        $payment = $order->payments()->first();

        $this->actingAs($buyer)->get(route('payments.gcash.cancel', $payment));

        $this->assertEquals('failed', $payment->fresh()->status);
        $this->assertEquals(Order::PENDING, $order->fresh()->status);
    }

    public function test_only_buyers_can_review_and_only_once(): void
    {
        [$seller, $artwork] = $this->makeSellerWithArtwork();
        $buyer = User::factory()->create();
        $item = $this->buy($buyer, $artwork)->items->first();

        $this->actingAs(User::factory()->create())->post(route('reviews.save', $item), ['rating' => 5])->assertForbidden();

        $this->actingAs($buyer)->post(route('reviews.save', $item), ['rating' => 5, 'comment' => 'Great']);
        $this->actingAs($buyer)->post(route('reviews.save', $item), ['rating' => 3, 'comment' => 'Edited']);

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['rating' => 3, 'comment' => 'Edited']);
        $this->assertEquals(1, Notification::where('user_id', $seller->id)->where('type', 'new_review')->count());
    }
}
