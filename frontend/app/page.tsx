import Link from 'next/link';

export default function HomePage() {
  return (
    <div className="page-container">
      <h1>School Management System</h1>
      <p>Use the links below to access authentication and management forms.</p>
      <div className="link-grid">
        <Link href="/login" className="card">Login / Register</Link>
        <Link href="/students" className="card">Student Form</Link>
        <Link href="/teachers" className="card">Teacher Form</Link>
      </div>
    </div>
  );
}
