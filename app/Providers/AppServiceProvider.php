<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CloudinaryImageUploader;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageUploader::class, function (Application $app): ImageUploader {
            // Uji tidak boleh menembak jaringan, apa pun isi .env-nya.
            if ($app->runningUnitTests() || Config::boolean('cloudinary.fake')) {
                return new FakeImageUploader;
            }

            $credentialsUrl = Config::get('cloudinary.url');

            // Sengaja melempar, bukan diam-diam memakai Fake: unggahan yang
            // "berhasil" tetapi tidak sampai ke mana pun jauh lebih sulit
            // disadari daripada galat yang terang-terangan.
            if (! is_string($credentialsUrl) || $credentialsUrl === '') {
                throw new RuntimeException(
                    'CLOUDINARY_URL belum diisi. Lihat docs/12-panduan-instalasi-deploy.md §12.5, '
                    .'atau setel CLOUDINARY_FAKE=true untuk bekerja tanpa akun Cloudinary.',
                );
            }

            return new CloudinaryImageUploader($credentialsUrl);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
