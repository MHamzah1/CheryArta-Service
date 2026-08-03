import { CalendarClock, CarFront, UserPlus } from 'lucide-react';

const LANGKAH = [
    {
        icon: UserPlus,
        title: 'Daftar atau Masuk',
        description:
            'Buat akun dengan email dan nomor WhatsApp aktif, lalu daftarkan kendaraan Anda satu kali saja.',
    },
    {
        icon: CalendarClock,
        title: 'Pilih Jadwal',
        description:
            'Pilih tanggal, jam yang masih tersedia, dan paket layanan. Sisa kuota tiap jam terlihat langsung saat memilih.',
    },
    {
        icon: CarFront,
        title: 'Datang & Pantau',
        description:
            'Datang sesuai jadwal. Status pengerjaan bisa dipantau lewat halaman booking atau menu Cek Servis dengan kode booking.',
    },
];

/** Tiga langkah booking (docs/06 §6.5). */
export default function BookingSteps() {
    return (
        <ol className="grid gap-4 md:grid-cols-3">
            {LANGKAH.map((langkah, indeks) => (
                <li key={langkah.title} className="bg-surface rounded-card shadow-card relative p-6">
                    <span
                        aria-hidden="true"
                        className="bg-brand-700 mb-4 flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white"
                    >
                        {indeks + 1}
                    </span>

                    <h3 className="text-ink flex items-center gap-2 text-base font-semibold">
                        <langkah.icon className="text-brand-700 h-5 w-5" aria-hidden="true" />
                        {langkah.title}
                    </h3>

                    <p className="text-ink-soft mt-2 text-sm">{langkah.description}</p>
                </li>
            ))}
        </ol>
    );
}
