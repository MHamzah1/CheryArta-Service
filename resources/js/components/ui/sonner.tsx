import { Toaster as SonnerToaster } from 'sonner';

/**
 * Pengganti alert() sistem lama. Dipasang sekali di setiap layout.
 */
export function Toaster() {
    return (
        <SonnerToaster
            position="top-center"
            richColors
            closeButton
            theme="system"
            toastOptions={{
                classNames: {
                    toast: 'rounded-card shadow-pop',
                },
            }}
        />
    );
}
