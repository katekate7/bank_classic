// src/pages/EditExpensePage.jsx
import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import AddExpenseForm from '../components/AddExpenseForm';
import api from '../api/axios';

const EditExpensePage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [initialData, setInitialData] = useState(null);

  useEffect(() => {
    const fetchExpense = async () => {
      try {
        const response = await api.get(`/api/expense/${id}`, { withCredentials: true });
        const { label, category, date, amount } = response.data;
        setInitialData({ label, category: category.name, date, amount });
      } catch (error) {
        console.error('Error fetching expense:', error);
      }
    };

    fetchExpense();
  }, [id]);

  const handleSave = async (updatedExpense) => {
    try {
      await api.put(`/api/expense/${id}`, updatedExpense, { withCredentials: true });
      navigate('/user/expense/');
    } catch (error) {
      console.error('Error updating expense:', error);
    }
  };

  if (!initialData) return <p>Loading...</p>;

  return (
    <AddExpenseForm
    onSave={handleSave}
    onCancel={() => navigate('/user/expense/')}
    initialData={initialData}
    />
  );
};

export default EditExpensePage;
