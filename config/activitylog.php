<?php

declare(strict_types=1);

/*
| A12 Activity Log (docs/07 §A12, docs/09 §9.7).
|
| Berkas bawaan paket, dengan dua nilai yang disesuaikan proyek ini. Sisanya
| dibiarkan apa adanya supaya perbedaannya terhadap bawaan mudah terlihat.
*/

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * Retensi 12 bulan (docs/07 §A12, docs/09 §9.7 "activity log 12 bulan").
     *
     * Perintah `activitylog:clean` sudah didaftarkan di routes/console.php,
     * tetapi PENJADWALNYA baru diaktifkan di Railway pada Tahap 12 (keputusan
     * grill #7). Sampai saat itu log menumpuk — dengan volume satu bengkel itu
     * tidak membahayakan apa pun, dan menyiapkan proses cron terpisah adalah
     * urusan infrastruktur yang tidak sebanding diselipkan ke tahap ini.
     */
    'delete_records_older_than_days' => 365,

    /*
     * Batas atas rentang tanggal pada saringan layar A12, dalam hari.
     *
     * Ditulis di sini, bukan di Form Request: `activity_log` adalah tabel yang
     * paling cepat membesar di antara semua tabel, dan angka batas yang hidup
     * di dua tempat adalah pola cacat B2 sistem lama.
     */
    'max_filter_range_days' => 366,

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => Spatie\Activitylog\Models\Activity::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    /*
     * This is the database connection that will be used by the migration and
     * the Activity model shipped with this package. In case it's not set
     * Laravel's database.default will be used instead.
     */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
