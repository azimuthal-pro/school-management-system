import { apiCall } from './api';

export const getUsers = async () => {
  const response = await apiCall('/users');
  return response;
};

export const updateUserRole = async (id: number, role: string) => {
  return apiCall(`/users/${id}`, {
    method: 'PUT',
    body: JSON.stringify({ role }),
  });
};

export const deleteUser = async (id: number) => {
  return apiCall(`/users/${id}`, {
    method: 'DELETE',
  });
};