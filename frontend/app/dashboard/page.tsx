'use client';

import Link from 'next/link';

export default function DashboardPage() {
  return (
    <div className="page-container">
      <h1>Dashboard</h1>
      <p>Access school management features here.</p>
      <div className="link-grid">
        <Link href="/students" className="card">Student Form</Link>
        <Link href="/teachers" className="card">Teacher Form</Link>
        <Link href="/reports" className="card">Reports</Link>
      </div>
    </div>
  );
}
