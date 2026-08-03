import { Input } from '@/components/ui/input';
import { ComponentProps } from 'react';

type Props = Omit<ComponentProps<typeof Input>, 'type' | 'onChange' | 'value'> & {
    value: string;
    onChange: (value: string) => void;
};

/**
 * Masukan nomor WhatsApp.
 *
 * Yang diketik pengguna dibiarkan apa adanya — server yang menormalkannya ke
 * bentuk 62xxxxxxxxxx lewat App\Support\PhoneNumber. Sistem lama memformat
 * nomor sambil diketik lalu menyimpan hasil berformat itu, sehingga isinya
 * tidak bisa dipakai untuk tautan wa.me (temuan di docs/08 §8.3).
 */
export default function PhoneInput({ value, onChange, ...props }: Props) {
    return (
        <Input
            type="tel"
            inputMode="tel"
            autoComplete="tel"
            placeholder="0812-3456-7890"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            {...props}
        />
    );
}
