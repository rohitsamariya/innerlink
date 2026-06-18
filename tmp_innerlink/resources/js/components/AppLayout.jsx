import { useState } from 'react';
import Sidebar from './Sidebar';

export default function AppLayout({ children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="flex h-screen bg-page">
            <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
            <div className="flex-1 flex flex-col min-w-0">
                <div className="lg:hidden flex items-center h-12 px-4 bg-header border-b border-border">
                    <button onClick={() => setSidebarOpen(true)} className="text-secondary hover:text-primary">
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <div className="flex-1" />
                    <span className="text-base font-bold text-primary">InnerLink</span>
                </div>
                {children}
            </div>
        </div>
    );
}
