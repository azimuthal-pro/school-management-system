'use client';

import { useState, useEffect } from 'react';
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, PieChart, Pie, Cell } from 'recharts';
import { getReports } from '../../services/teacherService';
import { getClasses } from '../../services/studentService';
import { useAuth } from '@/context/AuthContext';

interface Class {
  id: number;
  name: string;
}

interface ReportData {
  attendance?: any[];
  grades?: any[];
  summary?: any;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8', '#82CA9D'];

export default function ReportsPage() {
  const { token } = useAuth();
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [message, setMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [classes, setClasses] = useState<Class[]>([]);
  const [selectedClass, setSelectedClass] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [reportType, setReportType] = useState('attendance');

  useEffect(() => {
    const fetchClasses = async () => {
      if (!token) return;
      try {
        const classesData = await getReports();
        if (Array.isArray(classesData)) {
          setClasses(classesData);
        }
      } catch (error) {
        console.error('Failed to fetch classes:', error);
      }
    };

    fetchClasses();
  }, [token]);

  const generateReport = async () => {
    setMessage('');
    setLoading(true);
    try {
      const filters = {
        class_id: selectedClass ? Number(selectedClass) : undefined,
        start_date: startDate,
        end_date: endDate,
        type: reportType,
      };

      const data = await getReports(filters);
      setReportData(data);
      
      if (!data || (Array.isArray(data) && data.length === 0)) {
        setMessage('No data found for the selected filters.');
      }
    } catch (error: any) {
      setMessage(error.message || 'Failed to generate report');
    } finally {
      setLoading(false);
    }
  };

  const loadAttendanceSummary = async () => {
    setMessage('');
    setLoading(true);
    try {
      const today = new Date().toISOString().slice(0, 10);
      const filters = { start_date: today, type: 'attendance' };
      const data = await getReports(filters);
      
      // Mock data for demonstration
      const mockData = [
        { name: 'Present', value: 85 },
        { name: 'Absent', value: 10 },
        { name: 'Late', value: 5 },
      ];
      
      setReportData({ attendance: mockData });
    } catch (error: any) {
      setMessage(error.message || 'Failed to load attendance report');
    } finally {
      setLoading(false);
    }
  };

  const loadGradesSummary = async () => {
    setMessage('');
    setLoading(true);
    try {
      const data = await getReports({ type: 'grades' });
      
      // Mock data for demonstration
      const mockData = [
        { grade: 'A', count: 25 },
        { grade: 'B', count: 35 },
        { grade: 'C', count: 20 },
        { grade: 'D', count: 12 },
        { grade: 'F', count: 8 },
      ];
      
      setReportData({ grades: mockData });
    } catch (error: any) {
      setMessage(error.message || 'Failed to load grades report');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="page-container">
      <h1>Reports Dashboard</h1>
      
      <div className="form-card">
        <h2>Generate Custom Report</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <label className="form-group">
            <span>Report Type</span>
            <select value={reportType} onChange={(e) => setReportType(e.target.value)}>
              <option value="attendance">Attendance</option>
              <option value="grades">Grades</option>
            </select>
          </label>
          <label className="form-group">
            <span>Class (Optional)</span>
            <select value={selectedClass} onChange={(e) => setSelectedClass(e.target.value)}>
              <option value="">All Classes</option>
              {classes.map((cls) => (
                <option key={cls.id} value={cls.id}>
                  {cls.name}
                </option>
              ))}
            </select>
          </label>
          <label className="form-group">
            <span>Start Date</span>
            <input type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} />
          </label>
          <label className="form-group">
            <span>End Date</span>
            <input type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} />
          </label>
        </div>
        <button className="button" onClick={generateReport} disabled={loading}>
          {loading ? 'Generating...' : 'Generate Report'}
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <button className="button bg-green-600 hover:bg-green-700" onClick={loadAttendanceSummary} disabled={loading}>
          Load Attendance Summary
        </button>
        <button className="button bg-blue-600 hover:bg-blue-700" onClick={loadGradesSummary} disabled={loading}>
          Load Grade Summary
        </button>
      </div>

      {message && <p className={`message ${message.includes('No data') ? 'info' : 'error'}`}>{message}</p>}

      {reportData && (
        <div>
          {reportData.attendance && (
            <div className="form-card">
              <h2>Attendance Summary</h2>
              <div className="bg-gray-50 p-4 rounded">
                <ResponsiveContainer width="100%" height={300}>
                  <PieChart>
                    <Pie
                      data={reportData.attendance}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, value }) => `${name}: ${value}%`}
                      outerRadius={100}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {reportData.attendance.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>
          )}

          {reportData.grades && (
            <div className="form-card">
              <h2>Grade Distribution</h2>
              <div className="bg-gray-50 p-4 rounded">
                <ResponsiveContainer width="100%" height={300}>
                  <BarChart data={reportData.grades}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="grade" />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="count" fill="#8884d8" name="Number of Students" />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
