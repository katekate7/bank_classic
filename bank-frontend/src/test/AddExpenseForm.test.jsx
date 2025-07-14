import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import userEvent from '@testing-library/user-event'
import AddExpenseForm from '../components/AddExpenseForm'
import * as api from '../api/axios'

// Mock the API module
vi.mock('../api/axios', () => ({
  default: {
    get: vi.fn(),
  }
}))

describe('AddExpenseForm', () => {
  const mockOnSave = vi.fn()
  const mockOnCancel = vi.fn()
  
  const mockCategories = [
    { name: 'Food' },
    { name: 'Transportation' },
    { name: 'Entertainment' }
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    // Mock successful API call
    api.default.get.mockResolvedValue({ data: mockCategories })
  })

  it('renders form with correct title for new expense', async () => {
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    expect(screen.getByText('Add Expense')).toBeInTheDocument()
    expect(screen.getByLabelText('Label')).toBeInTheDocument()
    expect(screen.getByLabelText('Category')).toBeInTheDocument()
    expect(screen.getByLabelText('Date')).toBeInTheDocument()
    expect(screen.getByLabelText('Amount')).toBeInTheDocument()
    
    // Wait for categories to load
    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })
  })

  it('renders form with correct title for editing expense', async () => {
    const initialData = {
      label: 'Test Expense',
      category: 'Food',
      date: '2025-01-15',
      amount: 25.50
    }

    render(
      <AddExpenseForm 
        onSave={mockOnSave} 
        onCancel={mockOnCancel} 
        initialData={initialData}
      />
    )

    expect(screen.getByText('Change Expense')).toBeInTheDocument()
    
    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })
  })

  it('loads categories from API on mount', async () => {
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(api.default.get).toHaveBeenCalledWith('/api/categories')
    })

    await waitFor(() => {
      expect(screen.getByDisplayValue('Food')).toBeInTheDocument()
    })
  })

  it('populates form fields with initial data when editing', async () => {
    const initialData = {
      label: 'Coffee',
      category: 'Food',
      date: '2025-01-15T10:00:00Z',
      amount: 3.50
    }

    render(
      <AddExpenseForm 
        onSave={mockOnSave} 
        onCancel={mockOnCancel} 
        initialData={initialData}
      />
    )

    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })

    expect(screen.getByDisplayValue('Coffee')).toBeInTheDocument()
    expect(screen.getByDisplayValue('2025-01-15')).toBeInTheDocument()
    expect(screen.getByDisplayValue('3.5')).toBeInTheDocument()
  })

  it('handles form submission with valid data', async () => {
    const user = userEvent.setup()
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })

    // Fill out the form
    await user.type(screen.getByLabelText('Label'), 'Test Expense')
    await user.type(screen.getByLabelText('Date'), '2025-01-15')
    await user.type(screen.getByLabelText('Amount'), '25.50')

    // Submit the form
    await user.click(screen.getByText('Save'))

    expect(mockOnSave).toHaveBeenCalledWith({
      label: 'Test Expense',
      category: 'Food', // First category should be selected by default
      date: '2025-01-15',
      amount: '25.50'
    })
  })

  it('handles category selection', async () => {
    const user = userEvent.setup()
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })

    // Change category
    await user.selectOptions(screen.getByLabelText('Category'), 'Transportation')

    // Fill other fields and submit
    await user.type(screen.getByLabelText('Label'), 'Bus Ticket')
    await user.type(screen.getByLabelText('Date'), '2025-01-20')
    await user.type(screen.getByLabelText('Amount'), '2.50')
    await user.click(screen.getByText('Save'))

    expect(mockOnSave).toHaveBeenCalledWith({
      label: 'Bus Ticket',
      category: 'Transportation',
      date: '2025-01-20',
      amount: '2.50'
    })
  })

  it('handles cancel button click', async () => {
    const user = userEvent.setup()
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await user.click(screen.getByText('Cancel'))

    expect(mockOnCancel).toHaveBeenCalledTimes(1)
  })

  it('displays loading state while categories are being fetched', () => {
    // Mock a pending promise
    api.default.get.mockReturnValue(new Promise(() => {}))
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    expect(screen.getByText('Loading...')).toBeInTheDocument()
    expect(screen.getByRole('combobox')).toBeDisabled()
  })

  it('handles API error gracefully', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    api.default.get.mockRejectedValue(new Error('API Error'))
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(consoleSpy).toHaveBeenCalledWith('Error', expect.any(Error))
    })

    // Form should still render but with loading state
    expect(screen.getByText('Loading...')).toBeInTheDocument()
    
    consoleSpy.mockRestore()
  })

  it('validates required fields', async () => {
    const user = userEvent.setup()
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })

    // Try to submit without filling required fields
    await user.click(screen.getByText('Save'))

    // Check that onSave was not called (form validation should prevent submission)
    expect(mockOnSave).not.toHaveBeenCalled()
  })

  it('handles numeric amount input correctly', async () => {
    const user = userEvent.setup()
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })

    const amountInput = screen.getByLabelText('Amount')
    
    // Test decimal input
    await user.type(amountInput, '123.45')
    expect(amountInput).toHaveValue(123.45)

    // Clear and test integer input
    await user.clear(amountInput)
    await user.type(amountInput, '100')
    expect(amountInput).toHaveValue(100)
  })

  it('handles string categories vs object categories', async () => {
    // Test with string categories
    api.default.get.mockResolvedValue({ data: ['Food', 'Transportation'] })
    
    render(<AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />)

    await waitFor(() => {
      expect(screen.queryByText('Loading...')).not.toBeInTheDocument()
    })

    expect(screen.getByText('Food')).toBeInTheDocument()
    expect(screen.getByText('Transportation')).toBeInTheDocument()
  })
})
