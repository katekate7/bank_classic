import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BrowserRouter } from 'react-router-dom'
import ExpenseForm from '../../src/components/ExpenseForm'
import { AuthProvider } from '../../src/contexts/AuthContext'

// Mock API calls
const mockApiCall = vi.fn()
vi.mock('../../src/api/expenses', () => ({
  createExpense: mockApiCall,
  updateExpense: mockApiCall
}))

vi.mock('../../src/api/categories', () => ({
  getCategories: vi.fn(() => Promise.resolve([
    { id: 1, name: 'Food', color: '#FF5733' },
    { id: 2, name: 'Transport', color: '#3498DB' },
    { id: 3, name: 'Entertainment', color: '#9B59B6' }
  ]))
}))

const MockedExpenseForm = ({ expense = null, onSuccess = vi.fn() }) => (
  <BrowserRouter>
    <AuthProvider>
      <ExpenseForm expense={expense} onSuccess={onSuccess} />
    </AuthProvider>
  </BrowserRouter>
)

describe('ExpenseForm Integration Tests', () => {
  beforeEach(() => {
    mockApiCall.mockClear()
  })

  it('should create a new expense with complete form flow', async () => {
    const user = userEvent.setup()
    const onSuccess = vi.fn()
    
    mockApiCall.mockResolvedValue({
      id: 1,
      amount: 25.50,
      description: 'Lunch at restaurant',
      category: { id: 1, name: 'Food' },
      date: '2024-01-15'
    })

    render(<MockedExpenseForm onSuccess={onSuccess} />)

    // Wait for categories to load
    await waitFor(() => {
      expect(screen.getByLabelText(/category/i)).toBeInTheDocument()
    })

    // Fill out the form
    await user.type(screen.getByLabelText(/amount/i), '25.50')
    await user.type(screen.getByLabelText(/description/i), 'Lunch at restaurant')
    await user.selectOptions(screen.getByLabelText(/category/i), '1')
    await user.type(screen.getByLabelText(/date/i), '2024-01-15')

    // Submit the form
    await user.click(screen.getByRole('button', { name: /save|create/i }))

    // Verify API call
    await waitFor(() => {
      expect(mockApiCall).toHaveBeenCalledWith({
        amount: 25.50,
        description: 'Lunch at restaurant',
        category: 1,
        date: '2024-01-15'
      })
    })

    // Verify success callback
    expect(onSuccess).toHaveBeenCalled()
  })

  it('should update an existing expense', async () => {
    const user = userEvent.setup()
    const onSuccess = vi.fn()
    
    const existingExpense = {
      id: 1,
      amount: 15.00,
      description: 'Bus ticket',
      category: { id: 2, name: 'Transport' },
      date: '2024-01-10'
    }

    mockApiCall.mockResolvedValue({
      ...existingExpense,
      amount: 18.00,
      description: 'Updated bus ticket'
    })

    render(<MockedExpenseForm expense={existingExpense} onSuccess={onSuccess} />)

    // Wait for form to be populated
    await waitFor(() => {
      expect(screen.getByDisplayValue('15')).toBeInTheDocument()
      expect(screen.getByDisplayValue('Bus ticket')).toBeInTheDocument()
    })

    // Update the form
    const amountInput = screen.getByLabelText(/amount/i)
    const descriptionInput = screen.getByLabelText(/description/i)
    
    await user.clear(amountInput)
    await user.type(amountInput, '18.00')
    
    await user.clear(descriptionInput)
    await user.type(descriptionInput, 'Updated bus ticket')

    // Submit the form
    await user.click(screen.getByRole('button', { name: /save|update/i }))

    // Verify API call for update
    await waitFor(() => {
      expect(mockApiCall).toHaveBeenCalledWith(1, {
        amount: 18.00,
        description: 'Updated bus ticket',
        category: 2,
        date: '2024-01-10'
      })
    })

    expect(onSuccess).toHaveBeenCalled()
  })

  it('should handle validation errors', async () => {
    const user = userEvent.setup()
    
    mockApiCall.mockRejectedValue({
      response: {
        status: 400,
        data: {
          errors: {
            amount: 'Amount must be positive',
            description: 'Description is required'
          }
        }
      }
    })

    render(<MockedExpenseForm />)

    // Wait for form to load
    await waitFor(() => {
      expect(screen.getByLabelText(/category/i)).toBeInTheDocument()
    })

    // Submit form with invalid data
    await user.type(screen.getByLabelText(/amount/i), '-10')
    await user.click(screen.getByRole('button', { name: /save|create/i }))

    // Check for error messages
    await waitFor(() => {
      expect(screen.getByText(/amount must be positive/i)).toBeInTheDocument()
      expect(screen.getByText(/description is required/i)).toBeInTheDocument()
    })
  })

  it('should handle network errors gracefully', async () => {
    const user = userEvent.setup()
    
    mockApiCall.mockRejectedValue(new Error('Network error'))

    render(<MockedExpenseForm />)

    // Wait for form to load
    await waitFor(() => {
      expect(screen.getByLabelText(/category/i)).toBeInTheDocument()
    })

    // Fill and submit form
    await user.type(screen.getByLabelText(/amount/i), '25.50')
    await user.type(screen.getByLabelText(/description/i), 'Test expense')
    await user.selectOptions(screen.getByLabelText(/category/i), '1')
    await user.click(screen.getByRole('button', { name: /save|create/i }))

    // Check for error message
    await waitFor(() => {
      expect(screen.getByText(/error occurred/i)).toBeInTheDocument()
    })
  })
})
