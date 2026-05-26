import { apiCall } from './api';

export const createStudent = async (payload: {
  user_id: number;
  student_number: string;
  class_id: number;
  date_of_birth?: string;
  address?: string;
}) => {
  return apiCall('/students', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
};

export const getStudents = async () => {
  return apiCall('/students');
};

export const getClasses = async () => {
  return apiCall('/classes');
};

export const getUsers = async () => {
  return apiCall('/users');
};
