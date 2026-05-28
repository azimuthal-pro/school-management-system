import { apiCall } from './api';

export const getAttendance = async (filters?: { class_id?: number; date?: string; student_id?: number }) => {
  const queryParams = filters && Object.keys(filters).length > 0
    ? `?${new URLSearchParams(
        Object.entries(filters)
          .filter(([, value]) => value !== undefined)
          .map(([key, value]) => [key, String(value)])
      ).toString()}`
    : '';
  return apiCall(`/attendance${queryParams}`);
};

export const createAttendance = async (payload: {
  student_id: number;
  class_id: number;
  date: string;
  status: 'present' | 'absent' | 'late';
}) => {
  return apiCall('/attendance', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
};

export const updateAttendance = async (id: number, payload: {
  status?: 'present' | 'absent' | 'late';
  date?: string;
}) => {
  return apiCall(`/attendance/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  });
};

export const deleteAttendance = async (id: number) => {
  return apiCall(`/attendance/${id}`, {
    method: 'DELETE',
  });
};