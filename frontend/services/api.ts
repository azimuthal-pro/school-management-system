const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000';

export class ApiError extends Error {
  public statusCode: number;
  public errors: Record<string, string[]> | null;

  constructor(message: string, statusCode: number, errors: Record<string, string[]> | null = null) {
    super(message);
    this.name = 'ApiError';
    this.statusCode = statusCode;
    this.errors = errors;
  }
}

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

  // Handle errors with proper JSON parsing
  if (!response.ok) {
    let errorMessage = 'An error occurred';
    let fieldErrors: Record<string, string[]> | null = null;

    try {
      const errorData = await response.json();
      errorMessage = errorData.message || errorData.error || `HTTP ${response.status}`;
      fieldErrors = errorData.errors || null;
    } catch {
      const text = await response.text().catch(() => '');
      errorMessage = text || response.statusText || `HTTP ${response.status}`;
    }

    // Auto-logout on 401 (expired/invalid token)
    if (response.status === 401 && typeof window !== 'undefined') {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      window.location.href = '/login';
      throw new ApiError('Session expired. Please log in again.', 401);
    }

    throw new ApiError(errorMessage, response.status, fieldErrors);
  }

  // No content
  if (response.status === 204) return null;

  // Try parse JSON, fall back to text
  const text = await response.text();
  try {
    const parsed = text ? JSON.parse(text) : null;
    // Auto-unwrap backend response format: { success, data, message }
    if (parsed && typeof parsed === 'object' && 'success' in parsed && 'data' in parsed) {
      return parsed.data;
    }
    return parsed;
  } catch (err) {
    return text;
  }
};
