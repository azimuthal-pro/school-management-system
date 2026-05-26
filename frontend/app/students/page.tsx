'use client';

import { useState, useEffect } from 'react';
import { createStudent, getClasses, getUsers } from '../../services/studentService';
import { useAuth } from '@/context/AuthContext';

interface Class {
  id: number;
  name: string;
}

interface User {
  id: number;
  name: string;
  email: string;
}

export default function StudentPage() {
  const { token } = useAuth();
  const [userId, setUserId] = useState('');
  const [studentNumber, setStudentNumber] = useState('');
  const [classId, setClassId] = useState('');
  const [dob, setDob] = useState('');
  const [address, setAddress] = useState('');
  const [message, setMessage] = useState('');
  const [classes, setClasses] = useState<Class[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      if (!token) return;
      try {
        const [classesData, usersData] = await Promise.all([
          getClasses(),
          getUsers(),
        ]);
        setClasses(classesData || []);
        setUsers(usersData || []);
      } catch (error) {
        console.error('Failed to fetch data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [token]);

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setMessage('');

    try {
      await createStudent({
        user_id: Number(userId),
        student_number: studentNumber,
        class_id: Number(classId),
        date_of_birth: dob || undefined,
        address: address || undefined,
      });
      setMessage('Student created successfully');
      setUserId('');
      setStudentNumber('');
      setClassId('');
      setDob('');
      setAddress('');
    } catch (error: any) {
      setMessage(error.message || 'Failed to create student');
    }
  };

  if (loading) return <div className="page-container">Loading...</div>;

  return (
    <div className="page-container">
      <h1>Create Student</h1>
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
          <span>Student Number</span>
          <input value={studentNumber} onChange={(e) => setStudentNumber(e.target.value)} required />
        </label>
        <label className="form-group">
          <span>Class</span>
          <select value={classId} onChange={(e) => setClassId(e.target.value)} required>
            <option value="">Select a class</option>
            {classes.map((cls) => (
              <option key={cls.id} value={cls.id}>
                {cls.name}
              </option>
            ))}
          </select>
        </label>
        <label className="form-group">
          <span>Date of Birth</span>
          <input value={dob} onChange={(e) => setDob(e.target.value)} type="date" />
        </label>
        <label className="form-group">
          <span>Address</span>
          <textarea value={address} onChange={(e) => setAddress(e.target.value)} rows={4} />
        </label>
        <button className="button" type="submit">Create Student</button>
      </form>
      {message && <p className={`message ${message.includes('successfully') ? 'success' : 'error'}`}>{message}</p>}
    </div>
  );
}
