import { apiCall } from './api';

export const login = async (email: string, password: string) => {
  const response = await apiCall('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

  if (response.token) {
    localStorage.setItem('authToken', response.token);
  }

  return response;
};

export const logout = () => {
  localStorage.removeItem('authToken');
};

export const register = async (name: string, email: string, password: string) => {
  return apiCall('/auth/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password }),
  });
};
