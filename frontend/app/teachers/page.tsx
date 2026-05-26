'use client';

import { useState, useEffect } from 'react';
import { createTeacher, getUsers } from '../../services/teacherService';
import { useAuth } from '@/context/AuthContext';

interface User {
  id: number;
  name: string;
  email: string;
}

export default function TeacherPage() {
  const { token } = useAuth();
  const [userId, setUserId] = useState('');
  const [employeeNumber, setEmployeeNumber] = useState('');
  const [phone, setPhone] = useState('');
  const [message, setMessage] = useState('');
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchUsers = async () => {
      if (!token) return;
      try {
        const usersData = await getUsers();
        setUsers(usersData || []);
      } catch (error) {
        console.error('Failed to fetch users:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchUsers();
  }, [token]);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setMessage('');

    try {
      await createTeacher({
        user_id: Number(userId),
        employee_number: employeeNumber,
        phone: phone || undefined,
      });
      setMessage('Teacher created successfully');
      setUserId('');
      setEmployeeNumber('');
      setPhone('');
    } catch (error: any) {
      setMessage(error.message || 'Failed to create teacher');
    }
  };

  if (loading) return <div className="page-container">Loading...</div>;

  return (
    <div className="page-container">
      <h1>Create Teacher</h1>
      <form onSubmit={handleSubmit} className="form-card">
        <label className="form-group">
          <span>User</span>
          <select value={userId} onChange={(e) => setUserId(e.target.value)} required>
            <option value="">Select a user</option>
            {users.map((user) => (
              <option key={user.id} value={user.id}>
                {user.name} ({user.email})
              </option>
            ))}
          </select>
        </label>
        <label className="form-group">
          <span>Employee Number</span>
          <input value={employeeNumber} onChange={(e) => setEmployeeNumber(e.target.value)} required />
        </label>
        <label className="form-group">
          <span>Phone</span>
          <input value={phone} onChange={(e) => setPhone(e.target.value)} />
        </label>
        <button className="button" type="submit">Create Teacher</button>
      </form>
      {message && <p className={`message ${message.includes('successfully') ? 'success' : 'error'}`}>{message}</p>}
    </div>
  );
}
