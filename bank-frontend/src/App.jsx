// src/App.jsx
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { useState, useEffect } from 'react';
import LoginForm from './components/LoginForm';
import RegisterForm from './components/RegisterForm';
import Dashboard from './pages/Dashboard';
import AddExpenseFormPage from './components/AddExpenseForm';
import AddExpensePage from './pages/AddExpensePage';
import EditExpensePage from './pages/EditExpensePage';

// import EditExpenseFormPage from './pages/EditExpenseFormPage';
// import EditUserEmailPage from './pages/EditUserEmailPage';
import api from './api/axios';

function App() {
  const [authenticated, setAuthenticated] = useState(
    localStorage.getItem('authenticated') === 'true'
  );

  return (
    <Router>
      <Routes>
        {/* <Route path="/login" element={<LoginForm setAuthenticated={setAuthenticated} />} /> */}
        <Route path="/register" element={<RegisterForm setAuthenticated={setAuthenticated} />} />
        <Route path="/" element={<LoginForm setAuthenticated={setAuthenticated} />} />
        <Route path="/user/expense/" element={authenticated ? <Dashboard setAuthenticated={setAuthenticated} /> : <Navigate to="/" />}/>        <Route path="/user/expense/new" element={authenticated ? <AddExpensePage /> : <Navigate to="/" />} />
        <Route path="/user/expense/:id/edit" element={authenticated ? <EditExpensePage setAuthenticated={setAuthenticated} /> : <Navigate to="/" />}/>        <Route path="*" element={<Navigate to={authenticated ? "/" : "/"} />} />
      </Routes>
    </Router>
  );
}

export default App;