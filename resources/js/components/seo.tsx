import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

interface Props {
    /** Judul halaman tanpa nama perusahaan — Inertia menambahkannya sendiri. */
    title: string;
    description: string;
    /** URL gambar mutlak untuk kartu pratinjau; kosongkan bila halaman tidak punya. */
    image?: string | null;
    /** `article` untuk halaman detail; sisanya `website`. */
    type?: 'website' | 'article';
}

/**
 * Judul + Open Graph + Twitter Card untuk satu halaman (docs/06 §6.9).
 *
 * SATU-SATUNYA tempat tag meta halaman publik ditulis. Data terstruktur
 * schema.org TIDAK dibuat di sini: JSON-LD adalah <script>, dan menyisipkannya
 * dari React menuntut dangerouslySetInnerHTML yang dilarang keras — karena itu
 * ia disusun server di resources/views/app.blade.php.
 */
export default function Seo({ title, description, image = null, type = 'website' }: Props) {
    const page = usePage<SharedData>();
    const { appUrl, company } = page.props;

    // Tanpa query string: `/katalog?kategori=suv` dan `/katalog` adalah satu
    // halaman yang sama bagi mesin pencari.
    const canonical = `${appUrl}${page.url.split('?')[0]}`;
    const judulPenuh = `${title} | ${company.name}`;

    return (
        <Head title={title}>
            <meta head-key="description" name="description" content={description} />
            <link head-key="canonical" rel="canonical" href={canonical} />

            <meta head-key="og:type" property="og:type" content={type} />
            <meta head-key="og:site_name" property="og:site_name" content={company.name} />
            <meta head-key="og:title" property="og:title" content={judulPenuh} />
            <meta head-key="og:description" property="og:description" content={description} />
            <meta head-key="og:url" property="og:url" content={canonical} />
            <meta head-key="og:locale" property="og:locale" content="id_ID" />
            {/* `&&` menghasilkan `false` yang dibuang React.Children.toArray.
                JANGAN diganti fragment kosong: Head milik Inertia menyusun tag
                dari `element.type`, dan tipe Fragment berupa Symbol yang tidak
                bisa dijadikan teks. */}
            {image && <meta head-key="og:image" property="og:image" content={image} />}

            <meta head-key="twitter:card" name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />
            <meta head-key="twitter:title" name="twitter:title" content={judulPenuh} />
            <meta head-key="twitter:description" name="twitter:description" content={description} />
            {image && <meta head-key="twitter:image" name="twitter:image" content={image} />}
        </Head>
    );
}
