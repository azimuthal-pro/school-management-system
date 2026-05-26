const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

export const apiCall = async (endpoint: string, options: RequestInit = {}) => {
  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...((options.headers as Record<string, string>) || {}),
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const text = await response.text().catch(() => '');
    const body = text || response.statusText;
    throw new Error(`API Error: ${response.status} - ${body}`);
  }

  // No content
  if (response.status === 204) return null;

  // Try parse JSON, fall back to text
  const text = await response.text();
  try {
    return text ? JSON.parse(text) : null;
  } catch (err) {
    return text;
  }
};
