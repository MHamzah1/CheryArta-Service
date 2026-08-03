import BrandLockup from '@/components/brand-lockup';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    const { company } = usePage<SharedData>().props;

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-r">
                <div className="from-brand-900 to-brand-700 absolute inset-0 bg-gradient-to-br" />
                <Link href={route('home')} className="relative z-20 flex items-center text-white">
                    <BrandLockup size="md" />
                </Link>
                <div className="relative z-20 mt-auto space-y-4">
                    <p className="text-2xl font-semibold text-balance">{company.tagline}</p>
                    <div className="text-brand-100 space-y-1 text-sm">
                        <p>{company.address.full}</p>
                        {company.operating_hours_text.map((line) => (
                            <p key={line}>{line}</p>
                        ))}
                    </div>
                </div>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link href={route('home')} className="text-brand-700 relative z-20 flex items-center justify-center lg:hidden dark:text-white">
                        <BrandLockup size="lg" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-muted-foreground text-sm text-balance">{description}</p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
