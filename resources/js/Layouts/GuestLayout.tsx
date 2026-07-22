import { PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 px-4">
            <div className="mb-8 text-center">
                <span className="text-3xl font-bold text-indigo-600">FlowForge</span>
                <p className="mt-1 text-sm text-gray-500">AI Workflow Automation for SMEs</p>
            </div>
            <div className="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                {children}
            </div>
        </div>
    );
}
