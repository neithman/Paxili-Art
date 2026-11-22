<?php

namespace Database\Seeders;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\Download;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data so you can try the site right away.
 * Run with:  php artisan migrate:fresh --seed
 *
 * Accounts (password for all: "password"):
 *   admin@paxiliart.test   -> Admin
 *   seller@paxiliart.test  -> Buyer + Seller (has a store)
 *   artist@paxiliart.test  -> Buyer + Seller (has a store)
 *   buyer@paxiliart.test   -> Buyer only
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Users ----------
        $admin = $this->user('Paxili Admin', 'admin@paxiliart.test', true);
        $seller = $this->user('Mara Santos', 'seller@paxiliart.test');
        $artist = $this->user('Leo Navarro', 'artist@paxiliart.test');
        $buyer = $this->user('Bea Cruz', 'buyer@paxiliart.test');

        // ---------- Categories ----------
        $categories = collect(['Illustration', 'Digital Painting', 'Abstract', 'Pixel Art', 'Wallpapers', 'Patterns'])
            ->mapWithKeys(fn ($name) => [$name => Category::create(['name' => $name, 'slug' => Str::slug($name)])]);

        // ---------- Stores ----------
        $maraStore = Store::create([
            'user_id' => $seller->id, 'name' => 'Mara Studio', 'slug' => 'mara-studio',
            'description' => "Warm, bold abstract pieces made in Manila.\nEvery file is high resolution and ready to print.",
        ]);
        $leoStore = Store::create([
            'user_id' => $artist->id, 'name' => 'Navarro Pixels', 'slug' => 'navarro-pixels',
            'description' => 'Geometric patterns, wallpapers and pixel experiments.',
        ]);

        // ---------- Artwork (images are generated with PHP GD) ----------
        $items = [
            [$maraStore, 'Abstract', 'Ember Horizon', 450, 'sunset, warm, abstract', Artwork::APPROVED],
            [$maraStore, 'Digital Painting', 'Quiet Dunes', 620, 'desert, calm, minimal', Artwork::APPROVED],
            [$maraStore, 'Abstract', 'Orange Bloom', 380, 'flower, orange, modern', Artwork::APPROVED],
            [$maraStore, 'Illustration', 'Night Market', 750, 'city, night, lights', Artwork::APPROVED],
            [$maraStore, 'Abstract', 'Clay Moon', 300, 'moon, earthy', Artwork::PENDING],
            [$leoStore, 'Patterns', 'Grid Garden', 250, 'pattern, geometric', Artwork::APPROVED],
            [$leoStore, 'Wallpapers', 'Charcoal Waves', 199, 'wallpaper, dark, waves', Artwork::APPROVED],
            [$leoStore, 'Pixel Art', 'Tiny Sunrise', 150, 'pixel, retro, sunrise', Artwork::APPROVED],
            [$leoStore, 'Patterns', 'Sand Tiles', 220, 'tiles, neutral, pattern', Artwork::PENDING],
        ];

        $artworks = [];
        foreach ($items as $i => [$store, $category, $title, $price, $tags, $status]) {
            $slug = Str::slug($title) . '-' . Str::lower(Str::random(6));
            [$preview, $original] = $this->makeImages($slug, $i);

            $artworks[] = Artwork::create([
                'store_id' => $store->id,
                'category_id' => $categories[$category]->id,
                'title' => $title,
                'slug' => $slug,
                'description' => "\"{$title}\" – an original digital artwork by {$store->name}. High-resolution PNG (1600×1200).",
                'tags' => $tags,
                'price' => $price,
                'preview_path' => $preview,
                'file_path' => $original,
                'file_name' => Str::slug($title) . '.png',
                'status' => $status,
            ]);
        }

        // ---------- A few real (paid) demo orders so the charts are not empty ----------
        $this->paidOrder($buyer, [$artworks[0], $artworks[5]], 9, downloaded: true);
        $this->paidOrder($buyer, [$artworks[3]], 4, downloaded: false);
        $this->paidOrder($artist, [$artworks[1]], 2, downloaded: true);
        $this->paidOrder($seller, [$artworks[6], $artworks[7]], 0, downloaded: false);

        // ---------- Welcome notifications ----------
        Notification::send($admin, 'welcome', 'Welcome, admin!', '2 artworks are waiting for your review.', route('admin.artworks.index'));
        Notification::send($buyer, 'welcome', 'Welcome to PaxiliArt', 'Your purchases are ready in My Purchases.', route('purchases.index'));
    }

    private function user(string $name, string $email, bool $admin = false): User
    {
        $user = User::create(['name' => $name, 'email' => $email, 'password' => 'password']);
        $user->is_admin = $admin;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    /** Creates a paid order + payment (+ optional download) dated $daysAgo days back. */
    private function paidOrder(User $buyer, array $artworks, int $daysAgo, bool $downloaded): void
    {
        $date = now()->subDays($daysAgo)->subMinutes(rand(30, 300)); // always in the past
        $total = collect($artworks)->sum('price');

        $order = Order::create([
            'user_id' => $buyer->id,
            'order_number' => 'PXA-' . $date->format('ymd') . '-' . strtoupper(Str::random(5)),
            'total' => $total,
            'status' => Order::PAID,
            'paid_at' => $date,
        ]);
        $order->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

        Payment::create([
            'order_id' => $order->id, 'user_id' => $buyer->id, 'method' => 'gcash',
            'reference' => 'TXN-' . strtoupper(Str::random(12)), 'amount' => $total, 'mode' => 'test',
            'status' => Payment::SUCCESS, 'account_number' => '0917****567', 'paid_at' => $date,
        ]);

        foreach ($artworks as $artwork) {
            $item = $order->items()->create([
                'artwork_id' => $artwork->id, 'store_id' => $artwork->store_id,
                'title' => $artwork->title, 'price' => $artwork->price,
            ]);
            if ($downloaded) {
                Download::create([
                    'order_item_id' => $item->id, 'user_id' => $buyer->id, 'artwork_id' => $artwork->id,
                    'ip_address' => '127.0.0.1', 'downloaded_at' => $date->copy()->addMinutes(5),
                ]);
            }
        }
    }

    /**
     * Draws a simple abstract artwork with PHP's GD library.
     * Returns [public preview path, private original path].
     */
    private function makeImages(string $slug, int $seed): array
    {
        mt_srand($seed * 97 + 13);
        $w = 1600;
        $h = 1200;
        $img = imagecreatetruecolor($w, $h);

        $palettes = [
            [[37, 36, 34], [235, 94, 40], [255, 252, 242], [204, 197, 185]],
            [[255, 252, 242], [235, 94, 40], [64, 61, 57], [204, 197, 185]],
            [[64, 61, 57], [204, 197, 185], [235, 94, 40], [255, 252, 242]],
            [[235, 94, 40], [37, 36, 34], [255, 252, 242], [204, 197, 185]],
        ];
        $p = $palettes[$seed % count($palettes)];

        // Vertical gradient background
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = imagecolorallocate($img,
                (int) ($p[0][0] * (1 - $t) + $p[3][0] * $t * 0.5 + $p[0][0] * $t * 0.5),
                (int) ($p[0][1] * (1 - $t) + $p[3][1] * $t * 0.5 + $p[0][1] * $t * 0.5),
                (int) ($p[0][2] * (1 - $t) + $p[3][2] * $t * 0.5 + $p[0][2] * $t * 0.5));
            imageline($img, 0, $y, $w, $y, $c);
        }

        // Big sun / circles
        for ($i = 0; $i < 6; $i++) {
            $col = $p[1 + $i % 3];
            $color = imagecolorallocatealpha($img, $col[0], $col[1], $col[2], mt_rand(10, 70));
            $r = mt_rand(150, 520);
            imagefilledellipse($img, mt_rand(0, $w), mt_rand(0, $h), $r, $r, $color);
        }

        // Rolling "hills"
        for ($layer = 0; $layer < 3; $layer++) {
            $col = $p[($layer + 1) % 4];
            $color = imagecolorallocatealpha($img, $col[0], $col[1], $col[2], 20 + $layer * 15);
            $base = (int) ($h * (0.6 + $layer * 0.12));
            $points = [0, $h];
            for ($x = 0; $x <= $w; $x += 80) {
                $points[] = $x;
                $points[] = (int) ($base + sin(($x / $w) * M_PI * (2 + $layer) + $seed) * 70);
            }
            $points[] = $w;
            $points[] = $h;
            imagefilledpolygon($img, $points, $color);
        }

        // Thin lines
        $line = imagecolorallocatealpha($img, $p[2][0], $p[2][1], $p[2][2], 60);
        imagesetthickness($img, 3);
        for ($i = 0; $i < 5; $i++) {
            imageline($img, mt_rand(0, $w), mt_rand(0, $h / 2), mt_rand(0, $w), mt_rand($h / 2, $h), $line);
        }

        // Save the ORIGINAL (private)
        ob_start();
        imagepng($img);
        Storage::disk('local')->put($original = "artworks/{$slug}.png", ob_get_clean());

        // Save a smaller PREVIEW (public)
        $small = imagescale($img, 800, 600);
        ob_start();
        imagejpeg($small, null, 82);
        Storage::disk('public')->put($preview = "previews/{$slug}.jpg", ob_get_clean());

        imagedestroy($img);
        imagedestroy($small);

        return [$preview, $original];
    }
}
