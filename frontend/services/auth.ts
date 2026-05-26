import { apiCall } from './api';

export const login = async (email: string, password: string) => {
  const response = await apiCall('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
  
  if (response.token) {
    localStorage.setItem('auth_token', response.token);
  }

  if (response.user) {
    localStorage.setItem('auth_user', JSON.stringify(response.user));
  }

  return response;
};

export const logout = () => {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('auth_user');
};

export const register = async (name: string, email: string, password: string) => {
  return apiCall('/auth/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password }),
  });
};
