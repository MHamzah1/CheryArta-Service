import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { LoaderCircle, Upload } from 'lucide-react';
import { useRef, type FormEventHandler } from 'react';

interface FormData {
    images: File[];
    alts: string[];
    [key: string]: File[] | string[];
}

interface Props {
    carModelId: number;
}

/**
 * Unggah beberapa gambar sekaligus, satu teks alt per gambar.
 *
 * Teks alt diminta di sini — bukan sesudah unggah — supaya tidak ada gambar
 * yang pernah tersimpan tanpa alt (docs/06 §6.8). Server menolak dengan aturan
 * yang sama, jadi ini kenyamanan, bukan pengaman.
 */
export default function GalleryUploader({ carModelId }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({ images: [], alts: [] });
    const inputRef = useRef<HTMLInputElement>(null);

    const pilihBerkas = (files: FileList | null) => {
        const daftar = files ? Array.from(files) : [];

        setData('images', daftar);
        setData(
            'alts',
            daftar.map(() => ''),
        );
    };

    const ubahAlt = (index: number, nilai: string) => {
        setData(
            'alts',
            data.alts.map((alt, i) => (i === index ? nilai : alt)),
        );
    };

    const kosongkan = () => {
        reset();
        if (inputRef.current) inputRef.current.value = '';
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('admin.car-models.images.store', carModelId), {
            preserveScroll: true,
            onSuccess: kosongkan,
        });
    };

    return (
        <form onSubmit={submit} className="border-line bg-canvas grid gap-4 rounded-xl border p-4">
            <div className="grid gap-2">
                <Label htmlFor="images">Tambah Gambar</Label>
                <Input
                    id="images"
                    ref={inputRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    onChange={(e) => pilihBerkas(e.target.files)}
                    disabled={processing}
                    aria-describedby="images-hint"
                />
                <p id="images-hint" className="text-ink-soft text-sm">
                    JPG, PNG, atau WEBP. Paling besar 2 MB per gambar, maksimal 10 gambar sekali unggah.
                </p>
                <InputError message={errors.images} />
            </div>

            {data.images.map((file, index) => (
                <div key={`${file.name}-${index}`} className="grid gap-2">
                    <Label htmlFor={`alt-${index}`}>Teks alt untuk {file.name}</Label>
                    <Input
                        id={`alt-${index}`}
                        value={data.alts[index] ?? ''}
                        onChange={(e) => ubahAlt(index, e.target.value)}
                        maxLength={160}
                        placeholder="Tampak depan Tiggo 8 Pro warna putih"
                        disabled={processing}
                    />
                    {/* Galat per berkas datang berkunci indeks: `alts.0`, `images.0`. */}
                    <InputError message={errors[`alts.${index}`]} />
                    <InputError message={errors[`images.${index}`]} />
                </div>
            ))}

            {data.images.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    <Button type="submit" size="sm" disabled={processing}>
                        {processing ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                        ) : (
                            <Upload className="h-4 w-4" aria-hidden="true" />
                        )}
                        Unggah {data.images.length} Gambar
                    </Button>

                    <Button type="button" variant="outline" size="sm" onClick={kosongkan} disabled={processing}>
                        Batal
                    </Button>
                </div>
            )}
        </form>
    );
}
