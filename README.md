# PaxiliArt – Digital Art Marketplace

A multi-vendor marketplace for digital artwork, built with **Laravel 12 + MySQL + Blade + plain CSS + plain JavaScript**.
No React, no Vue, no Bootstrap, no Tailwind, no npm. Only PHP, HTML, CSS and JS.

- One account can **buy** and **sell** (sellers just open a store).
- Admins **approve or reject** artwork before it goes public.
- Buyers pay with **GCash** (via PayMongo, or a built-in test simulator) and can download each purchased artwork **exactly once**.
- In-app **notifications**, **reviews**, **sales charts** and **admin analytics**.

---

## 1. Run it on your computer (XAMPP)

You need: **XAMPP** (PHP 8.2+ and MySQL) and **Composer**.

1. Open the **XAMPP Control Panel** and click **Start** next to **MySQL**.
   (You do not need XAMPP's Apache, because Laravel has its own server.)
2. Open phpMyAdmin (http://localhost/phpmyadmin) and create an empty database called **`paxiliart`**.
3. Open a terminal **inside this project folder** and run:

```bash
composer install                    # only needed the first time / on a new PC
copy .env.example .env              # only if .env does not exist yet (Windows command)
php artisan key:generate            # only if APP_KEY in .env is empty
php artisan migrate:fresh --seed    # creates all tables + demo data
php artisan storage:link            # makes uploaded preview images visible
php artisan serve                   # starts the website
```

4. Open **http://127.0.0.1:8000** in your browser. 🎉

> **Tip:** `php artisan migrate:fresh --seed` deletes everything and rebuilds the demo data.
> Use it whenever you want a clean start.

### Demo accounts (password for all: `password`)

| Email                   | What it can do                     |
|-------------------------|------------------------------------|
| `admin@paxiliart.test`  | Admin dashboard, moderation        |
| `seller@paxiliart.test` | Buyer **and** seller (Mara Studio) |
| `artist@paxiliart.test` | Buyer **and** seller (Navarro Pixels) |
| `buyer@paxiliart.test`  | Buyer only (has some purchases)    |

### Paying with GCash

All payments use **GCash**. There are two modes, controlled by one line in `.env`:

| `.env` setting                       | What happens |
|--------------------------------------|--------------|
| `PAYMONGO_SECRET_KEY=` (empty)       | **Test mode.** A GCash *simulator* page opens. Type any number like `09171234567`, then click **Authorize** (success) or **Decline** (failed). No money moves. |
| `PAYMONGO_SECRET_KEY=sk_test_...`    | **PayMongo sandbox.** The real PayMongo/GCash checkout page opens, but with test money. |
| `PAYMONGO_SECRET_KEY=sk_live_...`    | **Live.** Buyers pay with their real GCash account. |

#### Turning on real GCash (PayMongo)

GCash does not let small websites connect directly, so we use **PayMongo**, a Philippine
payment gateway that handles GCash for you.

1. Sign up at https://dashboard.paymongo.com and verify your business (needed for live payments).
2. In the dashboard go to **Developers → API keys** and copy the **secret key**.
3. Paste it in `.env`: `PAYMONGO_SECRET_KEY=sk_test_xxxxxxxx`
4. Run `php artisan config:clear` and try a purchase.
5. When everything works, switch to the `sk_live_...` key.

How a live payment works (see `app/Services/GcashGateway.php`):

1. Buyer clicks **Pay with GCash** → we create a PayMongo checkout that only allows GCash.
2. Buyer is sent to GCash, logs in and approves.
3. GCash/PayMongo sends the buyer back to our site.
4. Our server **asks PayMongo** whether it was really paid. Only then is the order marked paid
   and the downloads unlocked. (We never trust the redirect alone.)

If the buyer closes the browser right after paying, the order page shows a
**Check payment status** button that asks PayMongo again.

Notes:
- The minimum GCash payment is **₱20.00** (PayMongo rule).
- Keep your secret key private, and never put it in Git or in JavaScript.
- PayMongo charges a fee per transaction. Check their pricing page.

### Forgot-password emails

`.env` has `MAIL_MAILER=log`, so emails are **not really sent**. The reset link is written
at the bottom of `storage/logs/laravel.log`. Copy the link from there into your browser.
Admins can also set a new password for any user in **Admin → Users → Edit**.

### Run the automated tests

```bash
php artisan test
```

They check the most important rules (approval, payment, one-time download, permissions, reviews).
They use a temporary database, so your real data is not touched.

---

## 2. Where is everything? (project map)

```
app/
  Http/
    Controllers/
      AuthController.php          login, register, logout, forgot password
      HomeController.php          home page
      MarketplaceController.php   browse/search/filter + artwork detail page
      StoreController.php         public store page + create/edit MY store
      CartController.php          add / remove cart items
      OrderController.php         checkout, start GCash payment, cancel
      PaymentController.php       GCash test simulator + coming back from GCash
      PurchaseController.php      My Purchases + ONE-TIME download
      ReviewController.php        create/edit reviews
      NotificationController.php  notification center
      Seller/                     seller dashboard + artwork CRUD
      Admin/                      admin dashboard, users, moderation, orders, categories
    Middleware/
      EnsureUserIsAdmin.php       "admin" – blocks non-admins
      EnsureUserHasStore.php      "seller" – sends users without a store to "Create store"
  Models/                         one file per table (User, Store, Artwork, Order, ...)
  Services/SalesReport.php        builds data for the daily/weekly/monthly graphs
  Services/GcashGateway.php       talks to PayMongo (real GCash payments)
  Services/PaymentService.php     marks orders paid/failed + sends notifications
  helpers.php                     money() and status_badge() helpers

database/
  migrations/                     table definitions
  seeders/DatabaseSeeder.php      demo users, stores, artwork (images drawn with PHP GD)

resources/views/                  Blade templates (HTML)
  layouts/app.blade.php           the main page layout (header, footer)
  partials/                       navbar, footer, alerts, chart, cards...
  admin/  seller/  marketplace/  orders/  purchases/  ...

public/
  css/app.css                     ALL the styling (colors at the top)
  js/app.js                       ALL the JavaScript (menus, chart, download popup)
  images/logo.png                 the PaxiliArt logo

routes/web.php                    every URL of the website, grouped and commented
tests/Feature/MarketplaceTest.php automated tests
```

### Brand colors (top of `public/css/app.css`)

| Variable      | Color     | Used for                |
|---------------|-----------|-------------------------|
| `--cream`     | `#FFFCF2` | page background         |
| `--sand`      | `#CCC5B9` | borders, muted elements |
| `--charcoal`  | `#403D39` | secondary text          |
| `--ink`       | `#252422` | main text, dark panels  |
| `--orange`    | `#EB5E28` | buttons, prices, accent |

Change a value once and the whole site updates.

The currency symbol is set in `.env` → `CURRENCY_SYMBOL=₱`.

---

## 3. Database tables

| Table             | What it stores |
|-------------------|----------------|
| `users`           | accounts (`is_admin` = admin) |
| `stores`          | one store per user (having a store = seller) |
| `categories`      | artwork categories |
| `artworks`        | products; `status` = pending / approved / rejected / removed |
| `carts`, `cart_items` | each user's cart |
| `orders`, `order_items` | checkouts; items keep a copy of title + price |
| `payments`        | every GCash payment attempt (pending / success / failed, test or live) |
| `downloads`       | one row per download – `order_item_id` is **UNIQUE** |
| `reviews`         | 1–5 stars, one per buyer per artwork |
| `notifications`   | in-app notifications (`read_at` empty = unread) |
| `moderation_logs` | admin approve / reject / delete history with reasons |

---

## 4. How the main features work

### Selling
1. A user clicks **Open your store** → `StoreController@store`.
2. They upload artwork → `Seller\ArtworkController@store`.
   - The **preview** image goes to `storage/app/public/previews` (public).
   - The **original file** goes to `storage/app/private/artworks` (**private**, no URL can reach it).
   - Status is always **pending**. Admins get a notification.
3. Editing an artwork sends it back to **pending** for another review.

### Moderation (admin)
`Admin\ArtworkController` → approve / reject (reason required) / delete (reason required).
Every action is saved in `moderation_logs` and the seller is notified.

### Buying
1. **Add to cart** – server checks: approved? not your own? not already bought?
2. **Checkout** – creates a *pending* order and empties the cart.
3. **Pay with GCash** – `OrderController@pay` → simulator (test) or GCash via PayMongo (live).
   On success `PaymentService::complete()` marks the order *paid*, the payment *success*,
   and notifies the buyer, sellers and admins.

### One-time download (`PurchaseController@download`)
The server checks, in order:
1. the order belongs to you,
2. the payment is verified (order *paid* + a *success* payment exists),
3. there is no download yet,
4. the file still exists.

Then it saves a `downloads` row (date, time, IP) and sends the private file.
Because `order_item_id` is UNIQUE in the `downloads` table, even two clicks at the
same moment cannot create two downloads.

In the browser, `public/js/app.js` disables the button and opens the optional **review popup**.

### Notifications
Anywhere in the code:

```php
Notification::send($user, 'type', 'Title', 'Message', 'link');
Notification::toAdmins('type', 'Title', 'Message', 'link');
```

---

## 5. Going live with real GCash

See **Paying with GCash → Turning on real GCash** above. It's only one line in `.env`.

---

## 6. Common problems

| Problem | Fix |
|---------|-----|
| `SQLSTATE[HY000] [2002] No connection` | MySQL is not running – start it in XAMPP. |
| `Unknown database 'paxiliart'` | Create the database in phpMyAdmin. |
| Images don't show | Run `php artisan storage:link`. |
| "Page expired" (419) | Refresh the page and try again (the form's security token expired). |
| "GCash is not available right now" | Check `PAYMONGO_SECRET_KEY` and the error in `storage/logs/laravel.log`. Run `php artisan config:clear` after editing `.env`. |
| Upload fails for big files | Raise `upload_max_filesize` and `post_max_size` in `C:\xampp\php\php.ini`, then restart. |
