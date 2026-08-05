<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CloudinaryImageUploader;
use App\Services\FakeImageUploader;
use App\Services\ImageUploader;
use App\Services\WhatsApp\ClickToChatNotifier;
use App\Services\WhatsApp\WhatsAppNotifier;
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

        // Notifikasi WhatsApp (docs/08 §8.7). Beralih ke gateway otomatis
        // kelak cukup menambah implementasi baru dan satu cabang di sini —
        // tidak ada controller, template, atau tabel yang perlu diubah.
        //
        // `bind`, BUKAN `singleton`: implementasinya menyimpan template yang
        // sudah dibaca agar layar jadwal tidak memicu satu kueri per booking.
        // Singleton akan membuat simpanan itu bertahan antar-permintaan di
        // dalam satu uji — template yang baru dinonaktifkan akan tampak masih
        // aktif pada permintaan berikutnya.
        $this->app->bind(WhatsAppNotifier::class, function (): WhatsAppNotifier {
            return match (Config::string('whatsapp.driver')) {
                'click_to_chat' => new ClickToChatNotifier,
                default => throw new RuntimeException(sprintf(
                    'WHATSAPP_DRIVER "%s" tidak dikenali. Satu-satunya driver yang tersedia saat ini adalah "click_to_chat".',
                    Config::string('whatsapp.driver'),
                )),
            };
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
