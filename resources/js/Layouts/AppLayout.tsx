import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import { PageProps } from '@/types';

export default function AppLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen flex bg-gray-50">
            {/* Sidebar */}
            <aside className="w-64 bg-white border-r border-gray-200 flex flex-col">
                <div className="h-16 flex items-center px-6 border-b border-gray-200">
                    <span className="text-xl font-bold text-indigo-600">FlowForge</span>
                </div>

                <nav className="flex-1 px-4 py-6 space-y-1">
                    <NavLink href="/dashboard" label="Dashboard" />
                    <NavLink href="/workflows" label="Workflows" />
                    <NavLink href="/connectors" label="Connectors" />
                    <NavLink href="/billing" label="Billing" />
                </nav>

                <div className="px-4 py-4 border-t border-gray-200">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-sm">
                            {auth.user.name.charAt(0).toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">{auth.user.name}</p>
                            <p className="text-xs text-gray-500 truncate">{auth.tenant.name}</p>
                        </div>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="mt-3 w-full text-left text-sm text-gray-500 hover:text-gray-700"
                    >
                        Sign out
                    </Link>
                </div>
            </aside>

            {/* Main content */}
            <div className="flex-1 flex flex-col min-w-0">
                <main className="flex-1 p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}

function NavLink({ href, label }: { href: string; label: string }) {
    const { url } = usePage();
    const active = url.startsWith(href);

    return (
        <Link
            href={href}
            className={`flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                active
                    ? 'bg-indigo-50 text-indigo-700'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            }`}
        >
            {label}
        </Link>
    );
}
