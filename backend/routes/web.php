<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\MediaImageController;
use App\Http\Controllers\ReviewSubmissionController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

foreach (['home' => '/', 'about' => '/about', 'services' => '/services', 'faqs' => '/faqs', 'gallery' => '/gallery', 'videos' => '/videos'] as $page => $path) {
    Route::get($path, WebsiteController::class)->defaults('page', $page)->name('public.'.$page);
}
foreach (['index.html' => '/', 'about-us.html' => '/about', 'about-us' => '/about', 'about.html' => '/about', 'about-page' => '/about', 'service-page.html' => '/services', 'service.html' => '/services', 'service-page' => '/services', 'faq-page.html' => '/faqs', 'faq.html' => '/faqs', 'faqs.html' => '/faqs', 'faq-page' => '/faqs', 'gallery-page.html' => '/gallery', 'gallery.html' => '/gallery', 'gallery-page' => '/gallery', 'video-page' => '/videos'] as $legacy => $path) {
    Route::redirect('/'.$legacy, $path, 301);
}
Route::get('/images/{media}', MediaImageController::class)->whereNumber('media')->name('media.image');
Route::post('/reviews', ReviewSubmissionController::class)->middleware('throttle:review-submissions')->name('public.reviews.store');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
    });

    Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'auth.session', 'admin'])->group(function (): void {
        Route::redirect('/', '/admin/dashboard');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->whereNumber('review')->name('reviews.update');
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->whereNumber('review')->name('reviews.destroy');
        Route::post('/reviews/{review}/restore', [ReviewController::class, 'restore'])->whereNumber('review')->name('reviews.restore');
        Route::delete('/reviews/{review}/permanent', [ReviewController::class, 'forceDestroy'])->whereNumber('review')->name('reviews.force-destroy');
        Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/media', [MediaController::class, 'index'])->name('media.index');
        Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:20,1')->name('media.store');
        Route::get('/media/{media}/edit', [MediaController::class, 'edit'])->whereNumber('media')->name('media.edit');
        Route::put('/media/{media}', [MediaController::class, 'update'])->whereNumber('media')->middleware('throttle:20,1')->name('media.update');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->whereNumber('media')->name('media.destroy');
        Route::group(['prefix' => 'content/{module}', 'as' => 'content.', 'where' => ['module' => implode('|', array_keys(config('content_editor.modules')))]], function (): void {
            Route::get('/', [ContentController::class, 'index'])->name('index');
            Route::get('/create', [ContentController::class, 'create'])->name('create');
            Route::post('/', [ContentController::class, 'store'])->name('store');
            Route::get('/{record}/edit', [ContentController::class, 'edit'])->whereNumber('record')->name('edit');
            Route::put('/{record}', [ContentController::class, 'update'])->whereNumber('record')->name('update');
            Route::patch('/{record}/visibility', [ContentController::class, 'visibility'])->whereNumber('record')->name('visibility');
            Route::delete('/{record}', [ContentController::class, 'destroy'])->whereNumber('record')->name('destroy');
            Route::post('/{record}/restore', [ContentController::class, 'restore'])->whereNumber('record')->name('restore');
            Route::delete('/{record}/permanent', [ContentController::class, 'forceDestroy'])->whereNumber('record')->name('force-destroy');
        });
        Route::get('/modules/{module}', ModuleController::class)->whereIn('module', array_keys(config('admin.modules')))->name('modules.show');
    });
});
