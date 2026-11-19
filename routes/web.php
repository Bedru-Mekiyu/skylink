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
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
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
 
 // Admin Videos 
 
Route::get('/videos', [AdminVideoController::class, 'index'])->name('videos.index');
Route::get('/videos/create', [AdminVideoController::class, 'create'])->name('videos.create');
Route::post('/videos', [AdminVideoController::class, 'store'])->name('videos.store');
Route::get('/videos/{video}/edit', [AdminVideoController::class, 'edit'])->name('videos.edit');
Route::put('/videos/{video}', [AdminVideoController::class, 'update'])->name('videos.update');
Route::delete('/videos/{video}', [AdminVideoController::class, 'destroy'])->name('videos.destroy');


Route::resource('services', ServiceadminController::class);

// Admin services
    Route::get('/services', [ServiceadminController::class, 'index'])->name('services.index');
    Route::get('/services/create', [ServiceadminController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceadminController::class, 'store'])->name('services.store');
    Route::get('/services/{services}/edit', [ServiceadminController::class, 'edit'])->name('services.edit');
    Route::put('/services/{services}', [ServiceadminController::class, 'update'])->name('services.update');
Route::delete('/services/{services}', [ServiceadminController::class, 'destroy'])->name('services.destroy');

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
    Route::get('/inbox/{inquiry}', [InboxController::class, 'show'])->name('inbox.show');
    Route::patch('/inbox/{inquiry}', [InboxController::class, 'update'])->name('inbox.update');
    Route::post('/inbox/{inquiry}/resend', [InboxController::class, 'resend'])->name('inbox.resend');
    Route::delete('/inbox/{inquiry}', [InboxController::class, 'destroy'])->name('inbox.destroy');

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
