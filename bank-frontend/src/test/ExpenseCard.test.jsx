import { render, screen, fireEvent } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import ExpenseCard from '../components/ExpenseCard'

// Mock window.location
const mockLocation = {
  href: '',
}
Object.defineProperty(window, 'location', {
  value: mockLocation,
  writable: true,
})

describe('ExpenseCard', () => {
  const mockExpense = {
    id: 1,
    label: 'Test Expense',
    amount: 25.50,
    date: '2025-01-15T00:00:00Z',
    category: {
      name: 'Food'
    }
  }

  const mockOnDelete = vi.fn()

  beforeEach(() => {
    vi.clearAllMocks()
    mockLocation.href = ''
  })

  it('renders expense information correctly', () => {
    render(<ExpenseCard expense={mockExpense} onDelete={mockOnDelete} />)

    expect(screen.getByText('Test Expense')).toBeInTheDocument()
    expect(screen.getByText('| Food')).toBeInTheDocument()
    expect(screen.getByText('Amount: 25.5 €')).toBeInTheDocument()
    
    // Check that date is formatted correctly
    const dateElement = screen.getByText('15 January 2025')
    expect(dateElement).toBeInTheDocument()
  })

  it('displays formatted date correctly', () => {
    const expenseWithDifferentDate = {
      ...mockExpense,
      date: '2025-12-25T00:00:00Z'
    }

    render(<ExpenseCard expense={expenseWithDifferentDate} onDelete={mockOnDelete} />)
    
    expect(screen.getByText('25 December 2025')).toBeInTheDocument()
  })

  it('handles edit button click', () => {
    render(<ExpenseCard expense={mockExpense} onDelete={mockOnDelete} />)

    const editButton = screen.getByText('✏️')
    fireEvent.click(editButton)

    expect(mockLocation.href).toBe('/user/expense/1/edit')
  })

  it('handles delete button click', () => {
    render(<ExpenseCard expense={mockExpense} onDelete={mockOnDelete} />)

    const deleteButton = screen.getByText('❌')
    fireEvent.click(deleteButton)

    expect(mockOnDelete).toHaveBeenCalledTimes(1)
  })

  it('renders with different expense data', () => {
    const differentExpense = {
      id: 2,
      label: 'Coffee',
      amount: 3.75,
      date: '2025-01-10T08:30:00Z',
      category: {
        name: 'Entertainment'
      }
    }

    render(<ExpenseCard expense={differentExpense} onDelete={mockOnDelete} />)

    expect(screen.getByText('Coffee')).toBeInTheDocument()
    expect(screen.getByText('| Entertainment')).toBeInTheDocument()
    expect(screen.getByText('Amount: 3.75 €')).toBeInTheDocument()
    expect(screen.getByText('10 January 2025')).toBeInTheDocument()
  })

  it('handles zero amount correctly', () => {
    const zeroAmountExpense = {
      ...mockExpense,
      amount: 0
    }

    render(<ExpenseCard expense={zeroAmountExpense} onDelete={mockOnDelete} />)
    
    expect(screen.getByText('Amount: 0 €')).toBeInTheDocument()
  })

  it('handles large amounts correctly', () => {
    const largeAmountExpense = {
      ...mockExpense,
      amount: 1234.56
    }

    render(<ExpenseCard expense={largeAmountExpense} onDelete={mockOnDelete} />)
    
    expect(screen.getByText('Amount: 1234.56 €')).toBeInTheDocument()
  })

  it('has correct button accessibility', () => {
    render(<ExpenseCard expense={mockExpense} onDelete={mockOnDelete} />)

    const editButton = screen.getByText('✏️')
    const deleteButton = screen.getByText('❌')

    expect(editButton).toBeInTheDocument()
    expect(deleteButton).toBeInTheDocument()
    
    // Check that buttons are clickable
    expect(editButton.tagName).toBe('BUTTON')
    expect(deleteButton.tagName).toBe('BUTTON')
  })
})
