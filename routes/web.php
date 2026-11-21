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
Route::get('/about', [AboutController::class, 'about'])->name('about');
Route::get('/contact', fn() => view('pages.contact'))->name('contact');
Route::get('/products', [ProductController::class, 'index'])->name('products');
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
Route::get('/subscribe/{plan}', fn($plan) => view('services.subscribe', ['plan' => $plan]));

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
    // Admin dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

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

    // Service categories: the cards and filter buttons on /services
    Route::resource('service-categories', ServiceCategoryAdminController::class)
        ->except('show')
        ->parameters(['service-categories' => 'serviceCategory']);


Route::resource('products', ProductadminController::class);


    // Subscribers
    Route::resource('subscribers', AdminSubscriberController::class)->only(['index']);

    // Contact messages
    Route::resource('contact-messages', ContactMessageController::class)->only(['index']);
    // The old Messages page and the inbox hold the same messages: viewing one there counts as read in the inbox.
    Route::post('/contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markRead'])->name('contact-messages.read');
    
    
    // Admin inbox: every website message (contact, support, quote, service, subscription).
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('/inbox/read-all', [InboxController::class, 'readAll'])->name('inbox.read-all');

    // My Profile: the logged-in admin's own name, email and password (rate limited; the user comes from the session)
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [AdminProfileController::class, 'update'])->middleware('throttle:20,1')->name('profile.update');
    Route::put('/profile/password', [AdminProfileController::class, 'password'])->middleware('throttle:5,1')->name('profile.password');

    // Anniversary ceremony control (mode, dates, admin preview).
    Route::get('/anniversary', [\App\Http\Controllers\Admin\AnniversaryAdminController::class, 'edit'])->name('anniversary.edit');
    Route::put('/anniversary', [\App\Http\Controllers\Admin\AnniversaryAdminController::class, 'update'])->name('anniversary.update');

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
    Route::get('/inbox/{inquiry}', [InboxController::class, 'show'])->name('inbox.show');
    Route::patch('/inbox/{inquiry}', [InboxController::class, 'update'])->name('inbox.update');
    Route::post('/inbox/{inquiry}/resend', [InboxController::class, 'resend'])->name('inbox.resend');
    Route::delete('/inbox/{inquiry}', [InboxController::class, 'destroy'])->name('inbox.destroy');
    Route::delete('/inbox', [InboxController::class, 'bulkDestroy'])->name('inbox.bulk-destroy');

//service admin
  


Route::get('/admin/subscriptions', [SubscriptionsnewController::class, 'index'])->name('subscriptions');

    // Newsletter: confirmation page + POST (CSRF via the web group), max 3 attempts per hour.
    Route::get('/newsletter', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
    Route::post('/newsletter/send', [NewsletterController::class, 'send'])
        ->middleware('throttle:3,60')
        ->name('newsletter.send');


    
});





//
// Auth scaffolding
// --------------------------------------------------
require __DIR__.'/auth.php';
