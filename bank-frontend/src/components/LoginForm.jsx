import { useState } from 'react';
import api from '../api/axios';
import { Link } from 'react-router-dom';
import { useNavigate } from 'react-router-dom';

const LoginForm = ({ setAuthenticated }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState('');
  const navigate = useNavigate(); 

  const handleLogin = async (e) => {
    e.preventDefault();
    try {
      await api.post(
        '/login',
        { email, password },
        {
          headers: { 'Content-Type': 'application/json' },
          withCredentials: true, 
        }
      );      
  
      localStorage.setItem('authenticated', 'true');
      setAuthenticated(true);
      navigate('/user/expense/');
    } catch (error) {
      setMessage('❌ Wrong email or password');
    }
  };
  

  return (
    <div className="container d-flex justify-content-center align-items-center vh-100">
      <h2 className="text-center mb-4">Welcome to MyBank</h2>
    <div className="card p-4 shadow" style={{ width: '100%', maxWidth: '400px' }}>

    <form onSubmit={handleLogin}>
      <div className="mb-3">
        <input
          type="email"
          className="form-control"
          placeholder="Email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
      </div>
      <div className="mb-3">
        <input
          type="password"
          className="form-control"
          placeholder="Password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
      </div>
      <button type="submit" className="btn btn-primary w-100">
        LOGIN
      </button>
      {message && <p className="text-danger text-center mt-2">{message}</p>}
      <p className="text-center mt-3">
        Not registered? <Link to="/register">Create an account</Link>
      </p>
    </form>
  </div>
</div>

  );
};

export default LoginForm;