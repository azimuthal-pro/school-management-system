'use client';

import { ReactNode } from 'react';
import { useAuth } from '@/context/AuthContext';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

export default function LayoutClient({ children }: { children: ReactNode }) {
  const { isAuthenticated, user, logout } = useAuth();
  const pathname = usePathname();

  // Don't show header on login page
  const showHeader = isAuthenticated && pathname !== '/login';

  return (
    <>
      {showHeader && (
        <header className="bg-blue-600 text-white p-4 shadow-md">
          <div className="flex justify-between items-center max-w-7xl mx-auto">
            <nav className="flex gap-6">
              <Link href="/dashboard" className="hover:text-blue-100">
                Dashboard
              </Link>
              <Link href="/students" className="hover:text-blue-100">
                Students
              </Link>
              <Link href="/teachers" className="hover:text-blue-100">
                Teachers
              </Link>
              <Link href="/attendance" className="hover:text-blue-100">
                Attendance
              </Link>
              <Link href="/reports" className="hover:text-blue-100">
                Reports
              </Link>
            </nav>
            <div className="flex items-center gap-4">
              <span className="text-sm">{user?.name || 'User'}</span>
              <button
                onClick={logout}
                className="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm"
              >
                Logout
              </button>
            </div>
          </div>
        </header>
      )}
      <main className="app-shell">{children}</main>
    </>
  );
}
