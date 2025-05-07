import { useEffect, useState } from 'react';
import ExpenseCard from '../components/ExpenseCard';
import api from '../api/axios';
import plusIcon from '../assets/plus.png'; 

const Dashboard = ({ setAuthenticated }) => {
  const [expenses, setExpenses] = useState([]);

  useEffect(() => {
    const fetchExpenses = async () => {
      try {
        const response = await api.get('/api/expenses', { withCredentials: true });
        setExpenses(response.data);
      } catch (error) {
        console.error('Error fetching expenses:', error);
      }
    };

    fetchExpenses();
  }, []);
  
  const handleLogout = () => {
    localStorage.removeItem('authenticated');
    setAuthenticated(false);
  };

  const handleDelete = async (id) => {
    try {
      await api.delete(`/api/expense/${id}`, { withCredentials: true });
      setExpenses(expenses.filter(e => e.id !== id));
    } catch (err) {
      console.error('Error deleting expense:', err);
    }
  };

  return (
    <div className="dashboard-body">
      <div id="dashboard-container">
        <div className="dashboard">
          <h2 className="dashboard-text">Dashboard</h2>

          <div className="add-expense">
            <img
              src={plusIcon}
              alt="Add Expense"
              className="add-expense-icon"
              onClick={() => window.location.href = '/user/expense/new'}
            />
            <button className="add-btn" onClick={() => window.location.href = '/user/expense/new'}>
              Add Expense
            </button>
          </div>

          <button className="logout" onClick={handleLogout}>Log out</button>
        </div>

        <div className="expense-list">
          {expenses.length > 0 ? (
            expenses.map((expense) => (
              <ExpenseCard
                key={expense.id}
                expense={expense}
                onDelete={() => handleDelete(expense.id)}
              />
            ))
          ) : (
            <p>No expenses yet.</p>
          )}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
