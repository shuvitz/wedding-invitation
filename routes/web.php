<?php

use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin SPA shell
|--------------------------------------------------------------------------
|
| Explicit, known paths only — registered before the public page route
| below since Laravel matches in registration order, and these must never
| be mistaken for a CMS page slug. Vue Router takes over client-side after
| the initial load; these routes only serve the very first HTML response
| (or a hard refresh) for each of them.
|
*/

// Named: Laravel's auth middleware redirects unauthenticated non-JSON
// requests (e.g. the CSV export link) to route('login').
Route::get('/login', fn () => view('app'))->name('login');
Route::get('/dashboard', fn () => view('app'));
Route::get('/admin/{any?}', fn () => view('app'))->where('any', '.*');

/*
|--------------------------------------------------------------------------
| Public website (Blade baseline)
|--------------------------------------------------------------------------
|
| Server-rendered published Pages — see PublicPageController. This is a
| baseline/compatibility renderer kept alongside the primary public
| frontend (Nuxt, in web/ — see web/README.md). Root `/` here still serves
| the admin SPA shell; the Blade renderer's homepage equivalent is Nuxt's
| GET /api/public/home (Page::is_home), not a route in this file.
|
*/

Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.page');

Route::get('/', fn () => view('app'));
