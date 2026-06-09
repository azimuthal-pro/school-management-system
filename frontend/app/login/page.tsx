'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { register } from '../../services/auth';
import { ApiError } from '../../services/api';

export default function LoginPage() {
  const [isRegister, setIsRegister] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [message, setMessage] = useState('');
  const [validationErrors, setValidationErrors] = useState<Record<string, string[]>>({});
  const router = useRouter();
  const { login } = useAuth();

  const handleSubmit = async (event: any) => {
    event.preventDefault();
    setMessage('');
    setValidationErrors({});

    try {
      if (isRegister) {
        await register(name, email, password);
        setMessage('Registration successful. Please log in.');
        setIsRegister(false);
      } else {
        await login(email, password);
        setMessage('Login successful. Redirecting...');
        router.push('/dashboard');
      }
    } catch (error: any) {
      if (error instanceof ApiError) {
        setMessage(error.message);
        if (error.errors) {
          setValidationErrors(error.errors);
        }
      } else {
        setMessage(error.message || 'Server error');
      }
    }
  };

  return (
    <div className="page-container">
      <h1>{isRegister ? 'Register' : 'Login'}</h1>
      <form onSubmit={handleSubmit} className="form-card">
        {isRegister && (
          <label className="form-group">
            <span>Name</span>
            <input value={name} onChange={(e) => setName(e.target.value)} />
            {validationErrors.name && (
              <span className="text-red-500 text-sm">{validationErrors.name[0]}</span>
            )}
          </label>
        )}
        <label className="form-group">
          <span>Email</span>
          <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" />
          {validationErrors.email && (
            <span className="text-red-500 text-sm">{validationErrors.email[0]}</span>
          )}
        </label>
        <label className="form-group">
          <span>Password</span>
          <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" />
          {validationErrors.password && (
            <span className="text-red-500 text-sm">{validationErrors.password[0]}</span>
          )}
        </label>
        <button className="button" type="submit">
          {isRegister ? 'Register' : 'Login'}
        </button>
      </form>
      <button className="link-button" onClick={() => { setIsRegister(!isRegister); setValidationErrors({}); }}>
        {isRegister ? 'Switch to Login' : 'Switch to Register'}
      </button>
      {message && <p className={`message ${message.includes('successful') ? 'text-green-600' : 'text-red-600'}`}>{message}</p>}
    </div>
  );
}
