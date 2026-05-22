<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\InvoiceController;
use App\Livewire\Account\Orders as AccountOrders;
use App\Livewire\Account\Settings as AccountSettings;
use App\Livewire\AccessInvite\Claim as AccessInviteClaim;
use App\Livewire\Admin\SendAccess;
use App\Livewire\Admin\Seasons\Create;
use App\Livewire\Admin\Seasons\Edit;
use App\Livewire\Admin\Seasons\Index;
use App\Livewire\Admin\Users\Show;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Checkout;
use App\Livewire\Dashboard;
use App\Livewire\Publications\Show as PublicationsShow;
use App\Livewire\Seasons\Index as SeasonsIndex;
use App\Livewire\Seasons\Show as SeasonsShow;
use App\Models\Publication;
use App\Services\PreviewPdfService;
use App\Support\ContentDisk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Public
Route::view('/', 'welcome')->name('home');
Route::view('/terms-and-conditions', 'legal.terms')->name('terms');
Route::get('/access/invite/{token}', AccessInviteClaim::class)->name('access-invite.claim');

// Guest auth
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::post('/preview-login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 422);
        }

        $request->session()->regenerate();

        return response()->json(['ok' => true]);
    })->name('preview.login');
});

// Google OAuth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

// Logout
Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout');

// Public season browsing (accessible without login)
Route::get('/seasons', SeasonsIndex::class)->name('seasons.index');
Route::get('/seasons/{slug}', SeasonsShow::class)->name('seasons.show');

/*
 * PDF stream: must work from PDF.js inside /flipbook-viewer/ iframe without relying on session
 * cookies (partitioned / third-party cookie rules). Valid temporary signed URL OR logged-in user
 * with publication access.
 */
Route::get('/publications/{slug}/stream-pdf', function (Request $request, string $slug) {
    $publication = Publication::where('slug', $slug)->published()->firstOrFail();
    if (! $publication->pdf_file) {
        abort(404);
    }
    // PDF URLs use URL::temporarySignedRoute(..., absolute: false); validate relative + absolute.
    $allowed = $request->hasValidSignature()
        || $request->hasValidRelativeSignature()
        || (auth()->check() && auth()->user()->hasAccessToPublication($publication));
    if (! $allowed) {
        abort(403);
    }

    return Storage::disk(ContentDisk::name())->response(
        $publication->pdf_file,
        (Str::slug($publication->title) ?: 'publication').'.pdf',
        [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ],
        'inline',
    );
})->name('publications.stream-pdf');

Route::get('/publications/{slug}/stream-preview-pdf', function (Request $request, string $slug) {
    $publication = Publication::where('slug', $slug)->published()->firstOrFail();
    if (! $publication->preview_pdf_file) {
        abort(404);
    }

    $allowed = $request->hasValidSignature() || $request->hasValidRelativeSignature();
    if (! $allowed) {
        abort(403);
    }

    return Storage::disk(ContentDisk::name())->response(
        $publication->preview_pdf_file,
        (Str::slug($publication->title) ?: 'publication').'-preview.pdf',
        [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ],
        'inline',
    );
})->name('publications.stream-preview-pdf');

Route::get('/publications/{slug}/preview', function (string $slug) {
    $publication = Publication::where('slug', $slug)->published()->with('season')->firstOrFail();
    abort_unless($publication->pdf_file, 404);

    if (! $publication->preview_pdf_file) {
        $previewPath = app(PreviewPdfService::class)->generate($publication, 5);
        if ($previewPath) {
            $publication->update(['preview_pdf_file' => $previewPath]);
        }
    }

    abort_unless($publication->preview_pdf_file, 404);

    $seasonPublications = collect([$publication]);
    $previous = null;
    $next = null;
    $pdfUrl = $publication->preview_pdf_url;
    $isPreview = true;
    $previewPageLimit = 5;

    return view('viewer', compact('publication', 'seasonPublications', 'previous', 'next', 'pdfUrl', 'isPreview', 'previewPageLimit'));
})->name('publications.preview');

Route::get('/checkout/{type}/{id}', Checkout::class)->name('checkout');

// Authenticated
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/orders', AccountOrders::class)->name('orders.index');
    Route::get('/orders/purchases/{purchase}/invoice', [InvoiceController::class, 'purchase'])->name('orders.invoice.purchase');
    Route::get('/orders/subscriptions/{subscription}/invoice', [InvoiceController::class, 'subscription'])->name('orders.invoice.subscription');
    Route::get('/publications/{slug}', PublicationsShow::class)->name('publications.show');
    Route::get('/publications/{slug}/view', function (string $slug) {
        $publication = Publication::where('slug', $slug)->published()->with('season')->firstOrFail();
        if (! auth()->user()->hasAccessToPublication($publication)) {
            return redirect()->route('publications.show', $publication->slug);
        }
        $seasonPublications = $publication->season->publications()->published()->orderBy('sort_order')->get();
        $previous = $publication->previous;
        $next = $publication->next;
        $pdfUrl = $publication->pdf_url;

        return view('viewer', compact('publication', 'seasonPublications', 'previous', 'next', 'pdfUrl'));
    })->name('publications.viewer');
    Route::get('/publications/{slug}/flipbook', function (string $slug) {
        $publication = Publication::where('slug', $slug)->published()->with('season')->firstOrFail();
        if (! auth()->user()->hasAccessToPublication($publication)) {
            abort(403);
        }

        $pdfUrl = $publication->pdf_url;
        $pdfMeta = [
            'size' => 0,
            'num_pages' => null,
            'width' => 1684,
            'height' => 2384,
        ];

        if ($publication->pdf_file && ! ContentDisk::isGoogle()) {
            $path = Storage::disk(ContentDisk::name())->path($publication->pdf_file);

            if (is_file($path)) {
                $pdfMeta['size'] = filesize($path) ?: 0;

                $header = file_get_contents($path, false, null, 0, 250000) ?: '';
                if (preg_match('#/MediaBox\s*\[\s*[-0-9.]+\s+[-0-9.]+\s+([-0-9.]+)\s+([-0-9.]+)\s*\]#', $header, $matches)) {
                    $pdfMeta['width'] = max(1, (int) round((float) $matches[1] * 2));
                    $pdfMeta['height'] = max(1, (int) round((float) $matches[2] * 2));
                }

                $contents = file_get_contents($path) ?: '';
                if (preg_match_all('#/Type\s*/Page\b#', $contents, $matches)) {
                    $pdfMeta['num_pages'] = count($matches[0]);
                }
            }
        }

        return view('flipbook-render', compact('publication', 'pdfUrl', 'pdfMeta'));
    })->name('publications.flipbook');
    Route::get('/account', AccountSettings::class)->name('account.settings');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/seasons', Index::class)->name('seasons.index');
    Route::get('/seasons/create', Create::class)->name('seasons.create');
    Route::get('/seasons/{season}/edit', Edit::class)->name('seasons.edit');
    Route::get('/publications', App\Livewire\Admin\Publications\Index::class)->name('publications.index');
    Route::get('/publications/create', App\Livewire\Admin\Publications\Create::class)->name('publications.create');
    Route::get('/publications/{publication}/edit', App\Livewire\Admin\Publications\Edit::class)->name('publications.edit');
    Route::get('/users', App\Livewire\Admin\Users\Index::class)->name('users.index');
    Route::get('/users/{user}', Show::class)->name('users.show');
    Route::get('/send-access', SendAccess::class)->name('send-access');
    Route::get('/discount-codes', App\Livewire\Admin\DiscountCodes\Index::class)->name('discount-codes.index');
    Route::get('/discount-codes/create', App\Livewire\Admin\DiscountCodes\Create::class)->name('discount-codes.create');
    Route::get('/discount-codes/{discountCode}/edit', App\Livewire\Admin\DiscountCodes\Edit::class)->name('discount-codes.edit');
    Route::get('/orders', App\Livewire\Admin\Orders\Index::class)->name('orders.index');
});
