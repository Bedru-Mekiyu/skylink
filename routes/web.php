<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PortfolioController as PublicPortfolioController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\NewsDisplayController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\Admin\ServiceadminController; 
use App\Http\Controllers\Admin\ServiceCategoryAdminController;
use App\Http\Controllers\Admin\AdminVideoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PortfolioController as AdminPortfolioController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\SubscriberController as AdminSubscriberController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\SubscriptionsnewController;
use App\Http\Controllers\Admin\ProductadminController; 



//

// (Removed an unauthenticated GET /admin/test that rendered the admin portfolio form.)

// Public routes (visible to anyone)
// --------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');
// Duplicate addresses of the home page go to the one canonical address (301).
Route::redirect('/home', '/', 301);
// IndexNow key file (only when INDEXNOW_KEY is set; see php artisan seo:ping).
Route::get('/{key}.txt', fn (string $key) => config('seo.indexnow_key') && hash_equals((string) config('seo.indexnow_key'), $key) ? response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']) : abort(404))->where('key', '[A-Za-z0-9-]{8,128}');
Route::get('/about', [AboutController::class, 'about'])->name('about');
Route::get('/contact', fn() => view('pages.contact'))->name('contact');
Route::get('/products', [ProductController::class, 'index'])->name('products');
Route::get('/products/item/{slug}', [ProductController::class, 'showBySlug'])->name('products.by-slug');
Route::get('/products/{category}/{slug}', [ProductController::class, 'show'])->name('products.detail');

// Portfolio (public)
Route::get('/portfolio', [PublicPortfolioController::class, 'index'])->name('portfolio');

// News (public)
Route::get('/news', [NewsDisplayController::class, 'index'])->name('content.index');
Route::get('/news/{slug}', [NewsDisplayController::class, 'show'])->name('news.show');
Route::get('/videos/{slug}', [NewsDisplayController::class, 'showVideo'])->name('videos.show'); 

// Services
Route::get('/services', [ServiceController::class, 'index'])->name('services');
Route::get('/services/category/{category}', [ServiceController::class, 'category'])->name('services.category');
Route::get('/services/detail/{subcategory}', [ServiceController::class, 'detail'])->name('services.detail');
Route::get('/services/{slug}', [ServiceController::class, 'resolveServiceOrCategory'])->name('services.by-slug');

// Public Legal & Policy Pages
Route::get('/legal/{slug}', [\App\Http\Controllers\PolicyController::class, 'show'])->name('legal.show');

// Every public message form (contact, support, quote, service request, plan
// subscription) posts here. Saved first, then emailed to CONTACT_INBOX.
// Replaces /contact-submit, /contact-message, /request-a-quote, /quote/request,
// /subscribe/process and /chat-submit.
Route::post('/inquiry', [InquiryController::class, 'store'])->middleware(['throttle:public-forms', 'form.guard'])->name('inquiry.store');

// Subscriptions
Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->middleware(['throttle:public-forms', 'form.guard'])->name('subscribe');
// Newsletter unsubscribe: personal signed link in every newsletter; the page asks to confirm (POST),
// so link scanners in mail programs cannot unsubscribe anyone by just opening it.
Route::get('/newsletter/unsubscribe/{subscriber}', [SubscriptionController::class, 'unsubscribeForm'])->middleware('signed')->name('newsletter.unsubscribe');
Route::post('/newsletter/unsubscribe/{subscriber}', [SubscriptionController::class, 'unsubscribe'])->middleware(['signed', 'throttle:public-forms'])->name('newsletter.unsubscribe.confirm');
// Cart & Request routes (frictionless session cart)
Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::get('/cart/count', [\App\Http\Controllers\CartController::class, 'count'])->name('cart.count');
Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/checkout', [\App\Http\Controllers\CheckoutController::class, 'process'])->middleware(['throttle:public-forms', 'form.guard'])->name('cart.checkout');
Route::get('/cart/success', [\App\Http\Controllers\CartController::class, 'success'])->name('cart.success');

// Online payment. The customer pages open only from a signed link (they show an order).
Route::get('/pay/{order:order_number}', [\App\Http\Controllers\PayController::class, 'show'])->middleware(['signed', 'throttle:60,1'])->name('pay.show');
Route::post('/pay/{order:order_number}', [\App\Http\Controllers\PayController::class, 'start'])->middleware(['signed', 'throttle:10,1'])->name('pay.start');
// Chapa may add its own parameters to the return address, so those are left out of the signature check.
Route::get('/pay/{order:order_number}/return', [\App\Http\Controllers\PayController::class, 'done'])->middleware(['signed:trx_ref,tx_ref,ref_id,status', 'throttle:30,1'])->name('pay.return');
Route::get('/webhooks/chapa/callback', [\App\Http\Controllers\ChapaWebhookController::class, 'callback'])->middleware('throttle:60,1')->name('webhooks.chapa.callback');
Route::post('/webhooks/chapa', [\App\Http\Controllers\ChapaWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('webhooks.chapa');

// An old page nothing links to any more; anyone holding the address lands on Services.
Route::redirect('/subscribe/{plan}', '/services');

// Newsletter sending: admin only (see the admin group below). The old public
// GET /send-newsletter trigger was removed: anyone could mail every subscriber.

//



// Auth routes (Laravel Breeze)
// --------------------------------------------------
// AFTER THE CHANGE
Route::middleware('auth')->group(function () {
    // We removed the old dashboard route.
    // The profile routes are still here and will work perfectly.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
// Was public (no auth) and rendered the admin dashboard. Now: login required;
// admins go to the admin dashboard, anyone else to the homepage.
Route::get('/dashboard', function () {
    return auth()->user()->is_admin
        ? redirect()->route('admin.dashboard')
        : redirect()->route('home');
})->middleware('auth')->name('dashboard');

//
// Admin-only routes
// --------------------------------------------------
Route::middleware(['auth', 'admin', 'bust.public.cache'])->prefix('admin')->name('admin.')->group(function () {
    // Every staff member
    // Admin dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    // My Profile: the logged-in admin's own name, email and password (rate limited; the user comes from the session)
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [AdminProfileController::class, 'update'])->middleware('throttle:20,1')->name('profile.update');
    Route::put('/profile/password', [AdminProfileController::class, 'password'])->middleware('throttle:5,1')->name('profile.password');


    // Super Admin only
    // Site Settings (global price toggle, staff, audit logs) - Super Admin only
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

        // Staff Accounts & Roles Management
        Route::post('staff/{staff}/toggle-active', [\App\Http\Controllers\Admin\StaffController::class, 'toggleActive'])->name('staff.toggle-active');
        Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class);

        // Admin Audit Logs
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        // Legal & Policy Pages Management
        Route::resource('policies', \App\Http\Controllers\Admin\PolicyController::class)->only(['index', 'edit', 'update']);
    });

    Route::middleware('role:super_admin')->group(function () {
    // Anniversary ceremony control (mode, dates, admin preview).
    Route::get('/anniversary', [\App\Http\Controllers\Admin\AnniversaryAdminController::class, 'edit'])->name('anniversary.edit');
    Route::put('/anniversary', [\App\Http\Controllers\Admin\AnniversaryAdminController::class, 'update'])->name('anniversary.update');

    // Newsletter: confirmation page + POST (CSRF via the web group), max 3 attempts per hour.
    Route::get('/newsletter', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::post('/newsletter/send', [NewsletterController::class, 'send'])
        ->middleware('throttle:3,60')
        ->name('newsletter.send');
    });

    // Catalog Prices Overview (bulk management & CSV export) - Super Admin and Finance
    Route::middleware('role:finance')->group(function () {
        Route::get('/prices-overview', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'index'])->name('prices.index');
        Route::post('/prices-overview', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'store'])->name('prices.store');
        Route::put('/prices-overview/{option}', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'update'])->name('prices.update');
        Route::delete('/prices-overview/{option}', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'destroy'])->name('prices.destroy');
        Route::put('/prices-overview/item/{type}/{id}/sale', [\App\Http\Controllers\Admin\SaleModeController::class, 'update'])->whereIn('type', ['service', 'product'])->whereNumber('id')->name('prices.sale');
        Route::get('/prices-overview/item/{type}/{id}', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'item'])->whereIn('type', ['service', 'product'])->whereNumber('id')->name('prices.item');
        Route::get('/prices-overview/export', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'export'])->name('prices.export');
        Route::post('/prices-overview/bulk-reviewed', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'bulkReviewed'])->name('prices.bulk-reviewed');
        Route::post('/prices-overview/{option}/toggle-review', [\App\Http\Controllers\Admin\PriceOverviewController::class, 'toggleReview'])->name('prices.toggle-review');
    });

    // Orders: finance and sales may look; only finance may export or re-verify a payment.
    Route::middleware('role:finance,sales')->group(function () {
        Route::get('/orders/renewals', [\App\Http\Controllers\Admin\OrderController::class, 'renewals'])->name('orders.renewals');
        Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    });
    Route::middleware('role:finance')->group(function () {
        Route::get('/orders/export', [\App\Http\Controllers\Admin\OrderController::class, 'export'])->name('orders.export');
        Route::post('/orders/{order}/reverify', [\App\Http\Controllers\Admin\OrderController::class, 'reverify'])->name('orders.reverify');
        Route::get('/billing', [\App\Http\Controllers\Admin\BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/{subscription}/send-link', [\App\Http\Controllers\Admin\BillingController::class, 'sendLink'])->middleware('throttle:20,1')->name('billing.send-link');
        Route::post('/billing/{subscription}/cancel', [\App\Http\Controllers\Admin\BillingController::class, 'cancel'])->name('billing.cancel');
    });

    // Sales: everything customers send us
    Route::middleware('role:sales')->group(function () {
    // Subscribers
    Route::resource('subscribers', AdminSubscriberController::class)->only(['index']);

    // Contact messages
    Route::resource('contact-messages', ContactMessageController::class)->only(['index']);
    // The old Messages page and the inbox hold the same messages: viewing one there counts as read in the inbox.
    Route::post('/contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markRead'])->name('contact-messages.read');
    
    
    // Admin inbox: every website message (contact, support, quote, service, subscription).
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/read-all', [InboxController::class, 'readAll'])->name('inbox.read-all');

    Route::get('/inbox/{inquiry}', [InboxController::class, 'show'])->whereNumber('inquiry')->name('inbox.show');
    Route::patch('/inbox/{inquiry}', [InboxController::class, 'update'])->name('inbox.update');
    Route::post('/inbox/{inquiry}/resend', [InboxController::class, 'resend'])->name('inbox.resend');
    Route::delete('/inbox/{inquiry}', [InboxController::class, 'destroy'])->name('inbox.destroy');
    Route::delete('/inbox', [InboxController::class, 'bulkDestroy'])->name('inbox.bulk-destroy');

Route::get('/admin/subscriptions', [SubscriptionsnewController::class, 'index'])->name('subscriptions');
    // Subscribers and plan subscriptions: delete one / selected, CSV export (admin only).
    Route::get('/subscribers/export', [AdminSubscriberController::class, 'export'])->name('subscribers.export');
    Route::delete('/subscribers/bulk', [AdminSubscriberController::class, 'bulkDestroy'])->middleware('throttle:60,1')->name('subscribers.bulk-destroy');
    Route::delete('/subscribers/{id}', [AdminSubscriberController::class, 'destroy'])->middleware('throttle:60,1')->whereNumber('id')->name('subscribers.destroy');
    Route::get('/subscriptions', [SubscriptionsnewController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/export', [SubscriptionsnewController::class, 'export'])->name('subscriptions.export');
    Route::delete('/subscriptions/bulk', [SubscriptionsnewController::class, 'bulkDestroy'])->middleware('throttle:60,1')->name('subscriptions.bulk-destroy');
    Route::delete('/subscriptions/{id}', [SubscriptionsnewController::class, 'destroy'])->middleware('throttle:60,1')->whereNumber('id')->name('subscriptions.destroy');
    Route::get('/inbox/export', [InboxController::class, 'export'])->name('inbox.export');
    });

    // Content Editor: catalogue and website content
    Route::middleware('role:content_editor')->group(function () {

    // Admin Portfolio
    Route::get('/portfolio', [AdminPortfolioController::class, 'adminIndex'])->name('portfolio.index');
    Route::get('/portfolio/create', [AdminPortfolioController::class, 'create'])->name('portfolio.create');
    Route::post('/portfolio', [AdminPortfolioController::class, 'store'])->name('portfolio.store');
    
Route::get('/portfolio/{portfolio}/edit', [AdminPortfolioController::class, 'edit'])->name('portfolio.edit');
    Route::put('/portfolio/{portfolio}', [AdminPortfolioController::class, 'update'])->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', [AdminPortfolioController::class, 'destroy'])->name('portfolio.destroy');

    // Admin News
    Route::get('/news', [AdminNewsController::class, 'index'])->name('news.index');
    Route::get('/news/similar', [AdminNewsController::class, 'similar'])->name('news.similar');
    Route::get('/news/duplicates-report', [AdminNewsController::class, 'duplicatesReport'])->name('news.duplicates-report');
    Route::post('/news/{news}/restore', [AdminNewsController::class, 'restore'])->withTrashed()->name('news.restore');
    Route::get('/news/create', [AdminNewsController::class, 'create'])->name('news.create');
    Route::post('/news', [AdminNewsController::class, 'store'])->name('news.store');
    Route::get('/news/{news}/edit', [AdminNewsController::class, 'edit'])->name('news.edit');
    Route::put('/news/{news}', [AdminNewsController::class, 'update'])->name('news.update');
    Route::delete('/news/{news}', [AdminNewsController::class, 'destroy'])->name('news.destroy');
    Route::delete('/news/{news}/permanent', [AdminNewsController::class, 'forceDestroy'])->withTrashed()->name('news.force-destroy');
    Route::delete('/partners/{partner}', [\App\Http\Controllers\Admin\PartnerController::class, 'destroy'])->middleware('throttle:60,1')->name('partners.destroy');
    Route::delete('/gallery/{image}', [\App\Http\Controllers\Admin\GalleryController::class, 'destroy'])->middleware('throttle:60,1')->name('gallery.destroy');
 
 // Admin Videos 
 
Route::get('/videos', [AdminVideoController::class, 'index'])->name('videos.index');
Route::get('/videos/create', [AdminVideoController::class, 'create'])->name('videos.create');
Route::post('/videos', [AdminVideoController::class, 'store'])->name('videos.store');
Route::get('/videos/{video}/edit', [AdminVideoController::class, 'edit'])->name('videos.edit');
Route::put('/videos/{video}', [AdminVideoController::class, 'update'])->name('videos.update');
Route::delete('/videos/{video}', [AdminVideoController::class, 'destroy'])->name('videos.destroy');


// Admin services. The resource defines index/create/store/edit/update/destroy; the same routes were once also
// written out by hand with the same names, which made `php artisan route:cache` fail ("Another route has already
// been assigned name admin.services.edit") and so would have broken the deploy.
Route::resource('services', ServiceadminController::class);
Route::post('services/{service}/duplicate', [ServiceadminController::class, 'duplicate'])->name('services.duplicate');
Route::post('services/{service}/toggle-publish', [ServiceadminController::class, 'togglePublish'])->name('services.toggle-publish');
Route::post('services/{service}/toggle-archive', [ServiceadminController::class, 'toggleArchive'])->name('services.toggle-archive');

    // Service categories: the cards and filter buttons on /services
    Route::resource('service-categories', ServiceCategoryAdminController::class)
        ->except('show')
        ->parameters(['service-categories' => 'serviceCategory']);


Route::resource('product-categories', \App\Http\Controllers\Admin\ProductCategoryAdminController::class)
        ->only(['index', 'update'])
        ->parameters(['product-categories' => 'productCategory']);

Route::resource('products', ProductadminController::class);
Route::post('products/{product}/duplicate', [ProductadminController::class, 'duplicate'])->name('products.duplicate');
Route::post('products/{product}/toggle-publish', [ProductadminController::class, 'togglePublish'])->name('products.toggle-publish');
Route::post('products/{product}/toggle-archive', [ProductadminController::class, 'toggleArchive'])->name('products.toggle-archive');

    // Trusted Partners (homepage logo marquee): add, edit, hide, order. Never deletes.
    Route::get('/partners', [\App\Http\Controllers\Admin\PartnerController::class, 'index'])->name('partners.index');
    Route::get('/partners/create', [\App\Http\Controllers\Admin\PartnerController::class, 'create'])->name('partners.create');
    Route::post('/partners', [\App\Http\Controllers\Admin\PartnerController::class, 'store'])->middleware('throttle:partner-upload')->name('partners.store');
    Route::put('/partners/reorder', [\App\Http\Controllers\Admin\PartnerController::class, 'reorder'])->middleware('throttle:60,1')->name('partners.reorder');
    Route::get('/partners/{partner}/edit', [\App\Http\Controllers\Admin\PartnerController::class, 'edit'])->name('partners.edit');
    Route::put('/partners/{partner}', [\App\Http\Controllers\Admin\PartnerController::class, 'update'])->middleware('throttle:partner-upload')->name('partners.update');
    Route::put('/partners/{partner}/toggle', [\App\Http\Controllers\Admin\PartnerController::class, 'toggle'])->middleware('throttle:60,1')->name('partners.toggle');

    // Testimonials (homepage + Services strip) and Departments (About page cards).
    $t = \App\Http\Controllers\Admin\TestimonialController::class;
    Route::get('/testimonials', [$t, 'index'])->name('testimonials.index');
    Route::get('/testimonials/create', [$t, 'create'])->name('testimonials.create');
    Route::post('/testimonials', [$t, 'store'])->middleware('throttle:partner-upload')->name('testimonials.store');
    Route::get('/testimonials/{testimonial}/edit', [$t, 'edit'])->name('testimonials.edit');
    Route::put('/testimonials/{testimonial}', [$t, 'update'])->middleware('throttle:partner-upload')->name('testimonials.update');
    Route::put('/testimonials/{testimonial}/toggle', [$t, 'toggle'])->middleware('throttle:60,1')->name('testimonials.toggle');
    Route::put('/testimonials/{testimonial}/move', [$t, 'move'])->middleware('throttle:60,1')->name('testimonials.move');
    Route::delete('/testimonials/{testimonial}', [$t, 'destroy'])->middleware('throttle:60,1')->name('testimonials.destroy');

    $d = \App\Http\Controllers\Admin\DepartmentController::class;
    Route::get('/departments', [$d, 'index'])->name('departments.index');
    Route::get('/departments/create', [$d, 'create'])->name('departments.create');
    Route::post('/departments', [$d, 'store'])->middleware('throttle:partner-upload')->name('departments.store');
    Route::get('/departments/{department}/edit', [$d, 'edit'])->name('departments.edit');
    Route::put('/departments/{department}', [$d, 'update'])->middleware('throttle:partner-upload')->name('departments.update');
    Route::put('/departments/{department}/toggle', [$d, 'toggle'])->middleware('throttle:60,1')->name('departments.toggle');
    Route::put('/departments/{department}/move', [$d, 'move'])->middleware('throttle:60,1')->name('departments.move');
    Route::delete('/departments/{department}', [$d, 'destroy'])->middleware('throttle:60,1')->name('departments.destroy');

    // About-page gallery: bulk upload, captions, order, hide. Never deletes.
    Route::get('/gallery', [\App\Http\Controllers\Admin\GalleryController::class, 'index'])->name('gallery.index');
    Route::post('/gallery', [\App\Http\Controllers\Admin\GalleryController::class, 'store'])->middleware('throttle:gallery-upload')->name('gallery.store');
    Route::put('/gallery/reorder', [\App\Http\Controllers\Admin\GalleryController::class, 'reorder'])->middleware('throttle:60,1')->name('gallery.reorder');
    Route::get('/gallery/{image}/edit', [\App\Http\Controllers\Admin\GalleryController::class, 'edit'])->name('gallery.edit');
    Route::put('/gallery/{image}', [\App\Http\Controllers\Admin\GalleryController::class, 'update'])->middleware('throttle:60,1')->name('gallery.update');
    Route::put('/gallery/{image}/toggle', [\App\Http\Controllers\Admin\GalleryController::class, 'toggle'])->middleware('throttle:60,1')->name('gallery.toggle');

    // Homepage hero video: upload, preview, restore previous, reset to the built-in default.
    Route::get('/hero-video', [\App\Http\Controllers\Admin\HeroVideoController::class, 'edit'])->name('hero-video.edit');
    Route::put('/hero-video', [\App\Http\Controllers\Admin\HeroVideoController::class, 'update'])->middleware('throttle:hero-upload')->name('hero-video.update');
    Route::put('/hero-video/reset', [\App\Http\Controllers\Admin\HeroVideoController::class, 'reset'])->middleware('throttle:20,1')->name('hero-video.reset');
    Route::put('/hero-video/restore', [\App\Http\Controllers\Admin\HeroVideoController::class, 'restore'])->middleware('throttle:20,1')->name('hero-video.restore');
    });
});

//
// Auth scaffolding
// --------------------------------------------------
require __DIR__.'/auth.php';
