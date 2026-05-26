'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { login, register } from '../../services/auth';

export default function LoginPage() {
  const [isRegister, setIsRegister] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [message, setMessage] = useState('');
  const router = useRouter();

  const handleSubmit = async (event: any) => {
    event.preventDefault();
    setMessage('');

    try {
      if (isRegister) {
        await register(name, email, password);
        setMessage('Registration successful. Please log in.');
        setIsRegister(false);
      } else {
        await login(email, password);
        setMessage('Login successful. Redirecting...');
        router.push('/');
      }
    } catch (error: any) {
      setMessage(error.message || 'Server error');
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
          </label>
        )}
        <label className="form-group">
          <span>Email</span>
          <input value={email} onChange={(e) => setEmail(e.target.value)} type="email" />
        </label>
        <label className="form-group">
          <span>Password</span>
          <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" />
        </label>
        <button className="button" type="submit">
          {isRegister ? 'Register' : 'Login'}
        </button>
      </form>
      <button className="link-button" onClick={() => setIsRegister(!isRegister)}>
        {isRegister ? 'Switch to Login' : 'Switch to Register'}
      </button>
      {message && <p className="message">{message}</p>}
    </div>
  );
}
