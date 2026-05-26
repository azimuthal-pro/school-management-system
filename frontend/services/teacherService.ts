import { apiCall } from './api';

export const createTeacher = async (payload: {
  user_id: number;
  employee_number: string;
  phone?: string;
}) => {
  return apiCall('/teachers', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
};

export const getTeachers = async () => {
  return apiCall('/teachers');
};

export const getUsers = async () => {
  return apiCall('/users');
};

export const getReports = async (filters?: {
  start_date?: string;
  end_date?: string;
  class_id?: number;
  type?: string;
}) => {
  const queryParams = filters
    ? `?${new URLSearchParams(
        Object.entries(filters)
          .filter(([, value]) => value !== undefined)
          .map(([key, value]) => [key, String(value)])
      ).toString()}`
    : '';
  return apiCall(`/reports${queryParams}`);
};
