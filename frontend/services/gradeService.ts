import { apiCall } from './api';

export const getGrades = async (filters?: { student_id?: number; class_id?: number; subject_id?: number }) => {
  const queryParams = filters && Object.keys(filters).length > 0
    ? `?${new URLSearchParams(
        Object.entries(filters)
          .filter(([, value]) => value !== undefined)
          .map(([key, value]) => [key, String(value)])
      ).toString()}`
    : '';
  return apiCall(`/grades${queryParams}`);
};

export const createGrade = async (payload: {
  student_id: number;
  subject_id: number;
  class_id?: number;
  term?: string;
  score: number;
}) => {
  return apiCall('/grades', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
};

export const updateGrade = async (id: number, payload: {
  score?: number;
  term?: string;
}) => {
  return apiCall(`/grades/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  });
};

export const deleteGrade = async (id: number) => {
  return apiCall(`/grades/${id}`, {
    method: 'DELETE',
  });
};