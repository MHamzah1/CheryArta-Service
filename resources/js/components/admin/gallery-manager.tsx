import GalleryItem from '@/components/admin/gallery-item';
import GalleryUploader from '@/components/admin/gallery-uploader';
import { cn } from '@/lib/utils';
import { type CarModelImage } from '@/types';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface Props {
    carModelId: number;
    images: CarModelImage[];
}

/**
 * Galeri katalog: unggah, seret untuk mengurutkan, tandai utama, sunting alt
 * (docs/07 §A4, roadmap 1.3.4).
 *
 * Urutan disalin ke state karena memang disunting — seretan harus terlihat
 * seketika, sebelum server menjawab. Tombol naik/turun bukan pelengkap:
 * seret-dan-lepas tidak dapat dioperasikan dengan papan tik (docs/06 §6.8).
 */
export default function GalleryManager({ carModelId, images }: Props) {
    const [urutan, setUrutan] = useState<CarModelImage[]>(images);
    const [diseret, setDiseret] = useState<number | null>(null);

    // Jawaban server kembali menjadi acuan setiap kali propsnya berubah.
    useEffect(() => setUrutan(images), [images]);

    const pindah = (dari: number, ke: number) => {
        if (ke < 0 || ke >= urutan.length || dari === ke) return;

        const baru = [...urutan];
        const [dipindah] = baru.splice(dari, 1);
        baru.splice(ke, 0, dipindah);

        setUrutan(baru);

        router.put(route('admin.car-models.images.reorder', carModelId), { ids: baru.map((image) => image.id) }, { preserveScroll: true });
    };

    return (
        <section aria-labelledby="judul-galeri" className="border-line bg-surface shadow-card grid gap-4 rounded-2xl border p-5">
            <div>
                <h2 id="judul-galeri" className="text-lg font-semibold">
                    Galeri
                </h2>
                <p className="text-ink-soft text-sm">
                    Gambar pertama otomatis menjadi gambar utama di kartu katalog. Seret kartu, atau pakai tombol panah, untuk mengurutkan.
                </p>
            </div>

            <GalleryUploader carModelId={carModelId} />

            {urutan.length === 0 ? (
                <p className="text-ink-soft text-sm">Belum ada gambar untuk model ini.</p>
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {urutan.map((image, index) => (
                        <li
                            key={image.id}
                            draggable
                            onDragStart={() => setDiseret(index)}
                            onDragOver={(e) => e.preventDefault()}
                            onDrop={() => {
                                if (diseret !== null) pindah(diseret, index);
                                setDiseret(null);
                            }}
                            onDragEnd={() => setDiseret(null)}
                            className={cn('border-line bg-surface rounded-xl border p-3', diseret === index && 'opacity-60')}
                        >
                            <GalleryItem
                                image={image}
                                index={index}
                                total={urutan.length}
                                onMove={pindah}
                                onPrimary={() =>
                                    router.put(route('admin.car-models.images.primary', [carModelId, image.id]), {}, { preserveScroll: true })
                                }
                                onDelete={() =>
                                    router.delete(route('admin.car-models.images.destroy', [carModelId, image.id]), {
                                        preserveScroll: true,
                                    })
                                }
                                onSaveAlt={(alt) =>
                                    router.put(route('admin.car-models.images.update', [carModelId, image.id]), { alt }, { preserveScroll: true })
                                }
                            />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
