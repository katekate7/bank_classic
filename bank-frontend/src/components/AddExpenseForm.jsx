import { useEffect, useState } from 'react';
import api from '../api/axios';

const AddExpenseForm = ({ onSave, onCancel, initialData = {} }) => {
  const [label, setLabel] = useState('');
  const [category, setCategory] = useState('');
  const [categories, setCategories] = useState([]);
  const [date, setDate] = useState('');
  const [amount, setAmount] = useState('');

  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await api.get('/api/categories');
        setCategories(response.data);
      } catch (error) {
        console.error('Error', error);
      }
    };

    fetchCategories();
  }, []);

  const isEdit = !!initialData.label;

  useEffect(() => {
    if (isEdit && categories.length > 0) {
      setLabel(initialData.label || '');
      setCategory(initialData.category || categories[0]);
      setDate(initialData.date ? initialData.date.slice(0, 10) : '');
      setAmount(initialData.amount || '');
    }

    if (!isEdit && categories.length > 0) {
      setCategory(categories[0]);
    }
  }, [initialData, categories, isEdit]);

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave({ label, category, date, amount });
  };

  return (
    <div className="container d-flex justify-content-center align-items-center vh-100">
      <div className="w-100" style={{ maxWidth: '600px' }}>
        <form className="card p-4 shadow" onSubmit={handleSubmit}>
          <h3 className="main-text">
            {initialData && initialData.label ? 'Change Expense' : 'Add Expense'}
          </h3>

          <div className="mb-3">
            <label className="form-label">Label</label>
            <input
              type="text"
              className="form-control"
              placeholder="e.g. Dress"
              value={label}
              onChange={e => setLabel(e.target.value)}
              required
            />
          </div>

          <div className="mb-3">
            <label className="form-label">Category</label>
            {categories.length > 0 ? (
              <select
                className="form-select"
                value={category}
                onChange={e => setCategory(e.target.value)}
                required
              >
                {categories.map((cat, idx) => (
                  <option key={idx} value={typeof cat === 'string' ? cat : cat.name}>
                    {typeof cat === 'string' ? cat : cat.name}
                  </option>
                ))}
              </select>
            ) : (
              <select className="form-select" disabled>
                <option>Loading...</option>
              </select>
            )}
          </div>

          <div className="mb-3">
            <label className="form-label">Date</label>
            <input
              type="date"
              className="form-control"
              value={date}
              onChange={e => setDate(e.target.value)}
              required
            />
          </div>

          <div className="mb-4">
            <label className="form-label">Amount</label>
            <input
              type="number"
              className="form-control"
              placeholder="200"
              value={amount}
              onChange={e => setAmount(e.target.value)}
              required
            />
          </div>

          <div className="buttons-expense">
            <button className="save" type="submit">Save</button>
            <button className="cancel" type="button" onClick={onCancel}>Cancel</button>
        </div>
        </form>
      </div>
    </div>
  );
};

export default AddExpenseForm;
