import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { type ReactNode } from 'react';

interface Props {
    trigger: ReactNode;
    title: string;
    description: string;
    /**
     * Teks tombol harus menyebut aksinya ("Hapus Kendaraan"), bukan "OK" —
     * aturan 40. Tombol berlabel netral membuat orang menyetujui hal yang
     * tidak mereka baca.
     */
    confirmLabel: string;
    cancelLabel?: string;
    onConfirm: () => void;
    processing?: boolean;
}

export default function ConfirmDialog({
    trigger,
    title,
    description,
    confirmLabel,
    cancelLabel = 'Batal',
    onConfirm,
    processing,
}: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                <DialogFooter className="gap-2 sm:gap-2">
                    <DialogClose asChild>
                        <Button variant="outline" disabled={processing}>
                            {cancelLabel}
                        </Button>
                    </DialogClose>

                    <Button variant="destructive" onClick={onConfirm} disabled={processing}>
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
