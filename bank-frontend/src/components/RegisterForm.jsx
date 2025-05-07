import { useState } from 'react';
import api from '../api/axios';
import { useNavigate } from 'react-router-dom';

const RegisterForm = ({ setAuthenticated }) => {
  const [email, setEmail]     = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState('');
  const navigate = useNavigate();

  const handleRegister = async (e) => {
    e.preventDefault();
    try {
      await api.post(
        '/register',
        { email, password },
        {
          headers: { 'Content-Type': 'application/json' },
          withCredentials: true,
        }
      );

      await api.post('/login', {
        email,
        password,
      });
  
      localStorage.setItem('authenticated', 'true'); 
      setAuthenticated(true);
      navigate('/user/expense/');
    } catch (err) {
      setMessage(err.response?.data?.error || '❌ Error occurred during registration');
    }
  };
  

  return (
    <div className="container d-flex justify-content-center align-items-center vh-100">
        <h2 className="text-center mb-4">Create your account </h2>
          <div className="card p-4 shadow" style={{ width: '100%', maxWidth: '400px' }}>

            <form onSubmit={handleRegister}>
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
            REGISTER
          </button>
            </form>
            </div>
            </div>
  );
};

export default RegisterForm;