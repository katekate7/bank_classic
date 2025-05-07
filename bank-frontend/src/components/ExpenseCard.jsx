// src/components/ExpenseCard.jsx
const ExpenseCard = ({ expense, onDelete }) => {
  return (
    <div className="expense-card">
      <strong>{expense.label}</strong> | {expense.category.name}
      <p>{new Date(expense.date).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })}</p>

      <p>Amount: {expense.amount} €</p>
      <div className="actions">
        <button onClick={() => window.location.href = `/user/expense/${expense.id}/edit`}>✏️</button>
        <button onClick={onDelete}>❌</button>
      </div>
    </div>
  );
};

export default ExpenseCard;
