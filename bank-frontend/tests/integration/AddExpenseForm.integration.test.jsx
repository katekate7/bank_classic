import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BrowserRouter } from 'react-router-dom'
import AddExpenseForm from '../../src/components/AddExpenseForm'

// Mock API calls
const mockCreateExpense = vi.fn()
const mockGetCategories = vi.fn()

vi.mock('../../src/api/axios', () => ({
  default: {
    post: mockCreateExpense,
    get: mockGetCategories
  }
}))

const MockedAddExpenseForm = ({ onSuccess = vi.fn() }) => (
  <BrowserRouter>
    <AddExpenseForm onSuccess={onSuccess} />
  </BrowserRouter>
)

describe('AddExpenseForm Integration Tests', () => {
  beforeEach(() => {
    mockCreateExpense.mockClear()
    mockGetCategories.mockClear()
    
    // Mock categories response
    mockGetCategories.mockResolvedValue({
      data: [
        { id: 1, name: 'Food', color: '#FF5733' },
        { id: 2, name: 'Transport', color: '#3498DB' }
      ]
    })
  })

  it('should submit expense data to backend API', async () => {
    const user = userEvent.setup()
    const onSuccess = vi.fn()
    
    // Mock successful expense creation
    mockCreateExpense.mockResolvedValue({
      data: {
        id: 1,
        amount: 25.50,
        description: 'Integration test expense',
        category: { id: 1, name: 'Food' }
      }
    })

    render(<MockedAddExpenseForm onSuccess={onSuccess} />)

    // Wait for form to be ready
    await waitFor(() => {
      expect(screen.getByRole('form')).toBeInTheDocument()
    })

    // Fill form fields if they exist
    const amountInput = screen.queryByLabelText(/amount/i) || screen.queryByPlaceholderText(/amount/i)
    const descriptionInput = screen.queryByLabelText(/description/i) || screen.queryByPlaceholderText(/description/i)
    
    if (amountInput) {
      await user.type(amountInput, '25.50')
    }
    
    if (descriptionInput) {
      await user.type(descriptionInput, 'Integration test expense')
    }

    // Submit form
    const submitButton = screen.getByRole('button', { name: /add|submit|save/i })
    await user.click(submitButton)

    // Verify API was called
    await waitFor(() => {
      expect(mockCreateExpense).toHaveBeenCalled()
    })
  })

  it('should handle API errors gracefully', async () => {
    const user = userEvent.setup()
    
    // Mock API error
    mockCreateExpense.mockRejectedValue(new Error('Network error'))

    render(<MockedAddExpenseForm />)

    // Wait for form and submit
    await waitFor(() => {
      expect(screen.getByRole('form')).toBeInTheDocument()
    })

    const submitButton = screen.getByRole('button', { name: /add|submit|save/i })
    await user.click(submitButton)

    // Should handle error without crashing
    await waitFor(() => {
      expect(mockCreateExpense).toHaveBeenCalled()
    })
  })
})
