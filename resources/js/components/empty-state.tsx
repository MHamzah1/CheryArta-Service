import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

interface Props {
    icon?: LucideIcon;
    title: string;
    description?: string;
    /** Ajakan tindakan — daftar kosong tanpa jalan keluar adalah jalan buntu. */
    action?: ReactNode;
}

export default function EmptyState({ icon: Icon, title, description, action }: Props) {
    return (
        <div className="border-line bg-surface rounded-2xl border border-dashed px-6 py-12 text-center">
            {Icon && (
                <div className="bg-brand-50 text-brand-700 mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full">
                    <Icon className="h-6 w-6" aria-hidden="true" />
                </div>
            )}

            <h2 className="text-lg font-semibold">{title}</h2>

            {description && <p className="text-ink-soft mx-auto mt-2 max-w-prose text-sm">{description}</p>}

            {action && <div className="mt-6">{action}</div>}
        </div>
    );
}
