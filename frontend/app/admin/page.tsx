'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { 
  getUsers, 
  updateUserRole, 
  deleteUser 
} from '../../services/userService';
import { getTeachers } from '../../services/teacherService';
import { getClasses } from '../../services/studentService';
import { getStudents } from '../../services/studentService';
import { getAttendance } from '../../services/attendanceService';
import { getGrades } from '../../services/gradeService';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  created_at?: string;
}

interface Teacher {
  id: number;
  user_id: number;
  employee_number: string;
  phone?: string;
  name?: string;
  email?: string;
}

interface Student {
  id: number;
  user_id: number;
  student_number: string;
  name?: string;
}

interface Class {
  id: number;
  name: string;
  year: number;
}

interface Attendance {
  id: number;
  student_id: number;
  date: string;
  status: string;
}

interface Grade {
  id: number;
  student_id: number;
  subject_id: number;
  score: number;
  grade: string;
}

type Tab = 'overview' | 'users' | 'teachers' | 'attendance' | 'grades' | 'reports' | 'settings';

export default function AdminDashboard() {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState<Tab>('overview');
  
  // Data states
  const [users, setUsers] = useState<User[]>([]);
  const [teachers, setTeachers] = useState<Teacher[]>([]);
  const [students, setStudents] = useState<Student[]>([]);
  const [classes, setClasses] = useState<Class[]>([]);
  const [attendance, setAttendance] = useState<Attendance[]>([]);
  const [grades, setGrades] = useState<Grade[]>([]);
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState('');

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const [usersData, teachersData, studentsData, classesData, attendanceData, gradesData] = await Promise.all([
          getUsers().catch(() => []),
          getTeachers().catch(() => []),
          getStudents().catch(() => []),
          getClasses().catch(() => []),
          getAttendance().catch(() => []),
          getGrades().catch(() => []),
        ]);
        
        setUsers(usersData.data || usersData || []);
        setTeachers(teachersData.data || teachersData || []);
        setStudents(studentsData.data || studentsData || []);
        setClasses(classesData.data || classesData || []);
        setAttendance(attendanceData.data || attendanceData || []);
        setGrades(gradesData.data || gradesData || []);
      } catch (error) {
        console.error('Error fetching data:', error);
      } finally {
        setLoading(false);
      }
    };
    
    fetchData();
  }, []);

  const handleRoleChange = async (userId: number, newRole: string) => {
    try {
      await updateUserRole(userId, newRole);
      setUsers(users.map(u => u.id === userId ? { ...u, role: newRole } : u));
      setMessage('User role updated successfully');
    } catch (error: any) {
      setMessage(error.message || 'Failed to update role');
    }
  };

  const handleDeleteUser = async (userId: number) => {
    if (!confirm('Are you sure you want to delete this user?')) return;
    
    try {
      await deleteUser(userId);
      setUsers(users.filter(u => u.id !== userId));
      setMessage('User deleted successfully');
    } catch (error: any) {
      setMessage(error.message || 'Failed to delete user');
    }
  };

  const renderOverview = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div className="form-card text-center">
        <h3 className="text-3xl font-bold text-blue-600">{users.length}</h3>
        <p className="text-gray-600">Total Users</p>
      </div>
      <div className="form-card text-center">
        <h3 className="text-3xl font-bold text-green-600">{teachers.length}</h3>
        <p className="text-gray-600">Total Teachers</p>
      </div>
      <div className="form-card text-center">
        <h3 className="text-3xl font-bold text-purple-600">{students.length}</h3>
        <p className="text-gray-600">Total Students</p>
      </div>
      <div className="form-card text-center">
        <h3 className="text-3xl font-bold text-orange-600">{classes.length}</h3>
        <p className="text-gray-600">Total Classes</p>
      </div>
    </div>
  );

  const renderUsers = () => (
    <div className="form-card">
      <h2 className="text-xl font-semibold mb-4">Manage Users</h2>
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="border-b">
              <th className="text-left p-2">Name</th>
              <th className="text-left p-2">Email</th>
              <th className="text-left p-2">Role</th>
              <th className="text-left p-2">Actions</th>
            </tr>
          </thead>
          <tbody>
            {users.map(user => (
              <tr key={user.id} className="border-b">
                <td className="p-2">{user.name}</td>
                <td className="p-2">{user.email}</td>
                <td className="p-2">
                  <select 
                    value={user.role} 
                    onChange={(e) => handleRoleChange(user.id, e.target.value)}
                    className="border rounded px-2 py-1"
                  >
                    <option value="admin">Admin</option>
                    <option value="teacher">Teacher</option>
                    <option value="student">Student</option>
                  </select>
                </td>
                <td className="p-2">
                  <button 
                    onClick={() => handleDeleteUser(user.id)}
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
  );

  const renderTeachers = () => (
    <div className="form-card">
      <h2 className="text-xl font-semibold mb-4">Manage Teachers</h2>
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="border-b">
              <th className="text-left p-2">Name</th>
              <th className="text-left p-2">Email</th>
              <th className="text-left p-2">Employee Number</th>
              <th className="text-left p-2">Phone</th>
            </tr>
          </thead>
          <tbody>
            {teachers.map(teacher => (
              <tr key={teacher.id} className="border-b">
                <td className="p-2">{teacher.name || 'N/A'}</td>
                <td className="p-2">{teacher.email || 'N/A'}</td>
                <td className="p-2">{teacher.employee_number}</td>
                <td className="p-2">{teacher.phone || 'N/A'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  const renderAttendance = () => (
    <div className="form-card">
      <h2 className="text-xl font-semibold mb-4">Attendance Overview</h2>
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="border-b">
              <th className="text-left p-2">Date</th>
              <th className="text-left p-2">Student ID</th>
              <th className="text-left p-2">Status</th>
            </tr>
          </thead>
          <tbody>
            {attendance.slice(0, 20).map(record => (
              <tr key={record.id} className="border-b">
                <td className="p-2">{record.date}</td>
                <td className="p-2">{record.student_id}</td>
                <td className="p-2">
                  <span className={`px-2 py-1 rounded text-xs ${
                    record.status === 'present' ? 'bg-green-100 text-green-800' :
                    record.status === 'absent' ? 'bg-red-100 text-red-800' :
                    'bg-yellow-100 text-yellow-800'
                  }`}>
                    {record.status}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  const renderGrades = () => (
    <div className="form-card">
      <h2 className="text-xl font-semibold mb-4">Grades Overview</h2>
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="border-b">
              <th className="text-left p-2">Student ID</th>
              <th className="text-left p-2">Subject ID</th>
              <th className="text-left p-2">Score</th>
              <th className="text-left p-2">Grade</th>
            </tr>
          </thead>
          <tbody>
            {grades.slice(0, 20).map(grade => (
              <tr key={grade.id} className="border-b">
                <td className="p-2">{grade.student_id}</td>
                <td className="p-2">{grade.subject_id}</td>
                <td className="p-2">{grade.score}</td>
                <td className="p-2">
                  <span className={`font-bold ${
                    grade.grade === 'A' ? 'text-green-600' :
                    grade.grade === 'B' ? 'text-blue-600' :
                    grade.grade === 'C' ? 'text-yellow-600' :
                    grade.grade === 'D' ? 'text-orange-600' : 'text-red-600'
                  }`}>
                    {grade.grade}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  const renderReports = () => (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div className="form-card">
        <h3 className="font-semibold mb-2">Attendance Summary</h3>
        <p>Total Records: {attendance.length}</p>
        <p>Present: {attendance.filter(a => a.status === 'present').length}</p>
        <p>Absent: {attendance.filter(a => a.status === 'absent').length}</p>
        <p>Late: {attendance.filter(a => a.status === 'late').length}</p>
      </div>
      <div className="form-card">
        <h3 className="font-semibold mb-2">Grade Statistics</h3>
        <p>Total Grades: {grades.length}</p>
        <p>Average Score: {grades.length > 0 ? (grades.reduce((sum, g) => sum + g.score, 0) / grades.length).toFixed(1) : 0}</p>
        <p>A Grades: {grades.filter(g => g.grade === 'A').length}</p>
        <p>F Grades: {grades.filter(g => g.grade === 'F').length}</p>
      </div>
    </div>
  );

  const renderSettings = () => (
    <div className="form-card">
      <h2 className="text-xl font-semibold mb-4">System Settings</h2>
      <p className="text-gray-600">System settings configuration coming soon...</p>
      <div className="mt-4 space-y-4">
        <div>
          <label className="block font-medium mb-1">School Name</label>
          <input type="text" className="w-full border rounded px-3 py-2" placeholder="Enter school name" />
        </div>
        <div>
          <label className="block font-medium mb-1">Academic Year</label>
          <input type="text" className="w-full border rounded px-3 py-2" placeholder="2025-2026" />
        </div>
        <button className="button">Save Settings</button>
      </div>
    </div>
  );

  const tabs: { id: Tab; label: string }[] = [
    { id: 'overview', label: 'Overview' },
    { id: 'users', label: 'Users' },
    { id: 'teachers', label: 'Teachers' },
    { id: 'attendance', label: 'Attendance' },
    { id: 'grades', label: 'Grades' },
    { id: 'reports', label: 'Reports' },
    { id: 'settings', label: 'Settings' },
  ];

  return (
    <div className="page-container">
      <h1>Admin Dashboard</h1>
      
      <nav className="flex gap-2 mb-6 border-b pb-4 overflow-x-auto">
        {tabs.map(tab => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={`px-4 py-2 rounded ${
              activeTab === tab.id 
                ? 'bg-blue-600 text-white' 
                : 'bg-gray-100 hover:bg-gray-200'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </nav>

      {message && <p className="message mb-4">{message}</p>}

      {loading ? (
        <p>Loading...</p>
      ) : (
        <>
          {activeTab === 'overview' && renderOverview()}
          {activeTab === 'users' && renderUsers()}
          {activeTab === 'teachers' && renderTeachers()}
          {activeTab === 'attendance' && renderAttendance()}
          {activeTab === 'grades' && renderGrades()}
          {activeTab === 'reports' && renderReports()}
          {activeTab === 'settings' && renderSettings()}
        </>
      )}
    </div>
  );
}