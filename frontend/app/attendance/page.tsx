'use client';

import { useState, useEffect } from 'react';
import { createAttendance, getAttendance, updateAttendance, deleteAttendance } from '../../services/attendanceService';
import { getStudents, getClasses } from '../../services/studentService';
import { useAuth } from '@/context/AuthContext';

interface Student {
  id: number;
  name?: string;
  student_number?: string;
}

interface Class {
  id: number;
  name: string;
}

interface AttendanceRecord {
  id: number;
  student_id: number;
  class_id: number;
  date: string;
  status: 'present' | 'absent' | 'late';
  student_name?: string;
  class_name?: string;
}

export default function AttendancePage() {
  const { token } = useAuth();
  const [studentId, setStudentId] = useState('');
  const [classId, setClassId] = useState('');
  const [date, setDate] = useState('');
  const [status, setStatus] = useState<'present' | 'absent' | 'late'>('present');
  const [message, setMessage] = useState('');
  const [records, setRecords] = useState<AttendanceRecord[]>([]);
  const [students, setStudents] = useState<Student[]>([]);
  const [classes, setClasses] = useState<Class[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      if (!token) return;
      setLoading(true);
      try {
        const [recordsData, studentsData, classesData] = await Promise.all([
          getAttendance().catch(() => []),
          getStudents().catch(() => []),
          getClasses().catch(() => []),
        ]);
        
        setRecords(recordsData.data || recordsData || []);
        setStudents(studentsData.data || studentsData || []);
        setClasses(classesData.data || classesData || []);
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

    if (!studentId || !classId || !date) {
      setMessage('All required fields must be filled');
      return;
    }

    try {
      await createAttendance({
        student_id: Number(studentId),
        class_id: Number(classId),
        date,
        status,
      });
      setMessage('Attendance recorded successfully');
      
      // Refresh records
      const recordsData = await getAttendance();
      setRecords(recordsData.data || recordsData || []);
      
      // Reset form
      setStudentId('');
      setClassId('');
      setDate('');
    } catch (error: any) {
      setMessage(error.message || 'Failed to record attendance');
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this record?')) return;
    
    try {
      await deleteAttendance(id);
      setRecords(records.filter(r => r.id !== id));
      setMessage('Record deleted successfully');
    } catch (error: any) {
      setMessage(error.message || 'Failed to delete record');
    }
  };

  if (loading) return <div className="page-container">Loading...</div>;

  return (
    <div className="page-container">
      <h1>Attendance Management</h1>
      
      <div className="form-card">
        <h2>Record Attendance</h2>
        <form onSubmit={handleSubmit}>
          <label className="form-group">
            <span>Student</span>
            <select value={studentId} onChange={(e) => setStudentId(e.target.value)} required>
              <option value="">Select a student</option>
              {students.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name || s.student_number}
                </option>
              ))}
            </select>
          </label>
          
          <label className="form-group">
            <span>Class</span>
            <select value={classId} onChange={(e) => setClassId(e.target.value)} required>
              <option value="">Select a class</option>
              {classes.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </label>
          
          <label className="form-group">
            <span>Date</span>
            <input 
              type="date" 
              value={date} 
              onChange={(e) => setDate(e.target.value)} 
              required 
            />
          </label>
          
          <label className="form-group">
            <span>Status</span>
            <select value={status} onChange={(e) => setStatus(e.target.value as 'present' | 'absent' | 'late')}>
              <option value="present">Present</option>
              <option value="absent">Absent</option>
              <option value="late">Late</option>
            </select>
          </label>
          
          <button className="button" type="submit">Record Attendance</button>
        </form>
      </div>

      {message && <p className="message">{message}</p>}

      <div className="form-card">
        <h2>Attendance Records</h2>
        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead>
              <tr className="border-b">
                <th className="text-left p-2">Date</th>
                <th className="text-left p-2">Student</th>
                <th className="text-left p-2">Class</th>
                <th className="text-left p-2">Status</th>
                <th className="text-left p-2">Actions</th>
              </tr>
            </thead>
            <tbody>
              {records.map((record) => (
                <tr key={record.id} className="border-b">
                  <td className="p-2">{record.date}</td>
                  <td className="p-2">{record.student_name || record.student_id}</td>
                  <td className="p-2">{record.class_name || record.class_id}</td>
                  <td className="p-2">
                    <span className={`px-2 py-1 rounded text-xs ${
                      record.status === 'present' ? 'bg-green-100 text-green-800' :
                      record.status === 'absent' ? 'bg-red-100 text-red-800' :
                      'bg-yellow-100 text-yellow-800'
                    }`}>
                      {record.status}
                    </span>
                  </td>
                  <td className="p-2">
                    <button 
                      onClick={() => handleDelete(record.id)}
                      className="text-red-600 hover:underline"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}