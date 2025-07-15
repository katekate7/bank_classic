import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { BrowserRouter } from 'react-router-dom'
import Dashboard from '../../src/pages/Dashboard'
import { AuthProvider } from '../../src/contexts/AuthContext'

// Mock API calls
const mockGetExpenses = vi.fn()
const mockGetCategories = vi.fn()
const mockDeleteExpense = vi.fn()

vi.mock('../../src/api/expenses', () => ({
  getExpenses: mockGetExpenses,
  deleteExpense: mockDeleteExpense
}))

vi.mock('../../src/api/categories', () => ({
  getCategories: mockGetCategories
}))

// Mock user context
const mockUser = {
  id: 1,
  email: 'test@example.com',
  firstName: 'Test',
  lastName: 'User'
}

vi.mock('../../src/contexts/AuthContext', async () => {
  const actual = await vi.importActual('../../src/contexts/AuthContext')
  return {
    ...actual,
    useAuth: () => ({
      user: mockUser,
      logout: vi.fn()
    })
  }
})

const MockedDashboard = () => (
  <BrowserRouter>
    <AuthProvider>
      <Dashboard />
    </AuthProvider>
  </BrowserRouter>
)

describe('Dashboard Integration Tests', () => {
  const mockExpenses = [
    {
      id: 1,
      amount: 25.50,
      description: 'Lunch at restaurant',
      category: { id: 1, name: 'Food', color: '#FF5733' },
      date: '2024-01-15',
      createdAt: '2024-01-15T12:00:00Z'
    },
    {
      id: 2,
      amount: 15.00,
      description: 'Bus ticket',
      category: { id: 2, name: 'Transport', color: '#3498DB' },
      date: '2024-01-14',
      createdAt: '2024-01-14T09:30:00Z'
    },
    {
      id: 3,
      amount: 30.00,
      description: 'Movie ticket',
      category: { id: 3, name: 'Entertainment', color: '#9B59B6' },
      date: '2024-01-13',
      createdAt: '2024-01-13T19:00:00Z'
    }
  ]

  const mockCategories = [
    { id: 1, name: 'Food', color: '#FF5733' },
    { id: 2, name: 'Transport', color: '#3498DB' },
    { id: 3, name: 'Entertainment', color: '#9B59B6' }
  ]

  beforeEach(() => {
    mockGetExpenses.mockClear()
    mockGetCategories.mockClear()
    mockDeleteExpense.mockClear()

    mockGetExpenses.mockResolvedValue({
      data: mockExpenses,
      total: mockExpenses.length,
      page: 1,
      pages: 1
    })
    
    mockGetCategories.mockResolvedValue(mockCategories)
  })

  it('should load and display expenses and categories on dashboard', async () => {
    render(<MockedDashboard />)

    // Verify API calls are made
    expect(mockGetExpenses).toHaveBeenCalled()
    expect(mockGetCategories).toHaveBeenCalled()

    // Wait for expenses to be displayed
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
      expect(screen.getByText('Bus ticket')).toBeInTheDocument()
      expect(screen.getByText('Movie ticket')).toBeInTheDocument()
    })

    // Verify amounts are displayed
    expect(screen.getByText('€25.50')).toBeInTheDocument()
    expect(screen.getByText('€15.00')).toBeInTheDocument()
    expect(screen.getByText('€30.00')).toBeInTheDocument()

    // Verify categories are displayed
    expect(screen.getByText('Food')).toBeInTheDocument()
    expect(screen.getByText('Transport')).toBeInTheDocument()
    expect(screen.getByText('Entertainment')).toBeInTheDocument()
  })

  it('should calculate and display total expenses correctly', async () => {
    render(<MockedDashboard />)

    await waitFor(() => {
      // Total should be 25.50 + 15.00 + 30.00 = 70.50
      expect(screen.getByText(/€70\.50/)).toBeInTheDocument()
    })
  })

  it('should filter expenses by category', async () => {
    const user = userEvent.setup()
    render(<MockedDashboard />)

    // Wait for initial load
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
    })

    // Find and click category filter
    const foodFilter = screen.getByText('Food')
    await user.click(foodFilter)

    // Should show only food expenses
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
      expect(screen.queryByText('Bus ticket')).not.toBeInTheDocument()
      expect(screen.queryByText('Movie ticket')).not.toBeInTheDocument()
    })
  })

  it('should handle expense deletion flow', async () => {
    const user = userEvent.setup()
    
    mockDeleteExpense.mockResolvedValue({ success: true })
    
    // Mock updated expenses list after deletion
    const updatedExpenses = mockExpenses.filter(exp => exp.id !== 1)
    mockGetExpenses.mockResolvedValueOnce({
      data: mockExpenses,
      total: mockExpenses.length
    }).mockResolvedValueOnce({
      data: updatedExpenses,
      total: updatedExpenses.length
    })

    render(<MockedDashboard />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
    })

    // Find and click delete button for first expense
    const deleteButtons = screen.getAllByLabelText(/delete/i)
    await user.click(deleteButtons[0])

    // Confirm deletion
    const confirmButton = screen.getByText(/confirm|yes|delete/i)
    await user.click(confirmButton)

    // Verify delete API call
    await waitFor(() => {
      expect(mockDeleteExpense).toHaveBeenCalledWith(1)
    })

    // Verify expense is removed from display
    await waitFor(() => {
      expect(screen.queryByText('Lunch at restaurant')).not.toBeInTheDocument()
      expect(screen.getByText('Bus ticket')).toBeInTheDocument()
      expect(screen.getByText('Movie ticket')).toBeInTheDocument()
    })
  })

  it('should search expenses by description', async () => {
    const user = userEvent.setup()
    render(<MockedDashboard />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
    })

    // Find search input and search for "lunch"
    const searchInput = screen.getByPlaceholderText(/search/i)
    await user.type(searchInput, 'lunch')

    // Should show only matching expense
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
      expect(screen.queryByText('Bus ticket')).not.toBeInTheDocument()
      expect(screen.queryByText('Movie ticket')).not.toBeInTheDocument()
    })
  })

  it('should sort expenses by different criteria', async () => {
    const user = userEvent.setup()
    render(<MockedDashboard />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
    })

    // Find sort dropdown
    const sortSelect = screen.getByLabelText(/sort/i)
    
    // Sort by amount (descending)
    await user.selectOptions(sortSelect, 'amount-desc')

    // Verify order: Movie (30.00), Lunch (25.50), Bus (15.00)
    const expenseElements = screen.getAllByTestId(/expense-item/i)
    expect(expenseElements[0]).toHaveTextContent('Movie ticket')
    expect(expenseElements[1]).toHaveTextContent('Lunch at restaurant')
    expect(expenseElements[2]).toHaveTextContent('Bus ticket')
  })

  it('should handle pagination when there are many expenses', async () => {
    const user = userEvent.setup()
    
    // Mock paginated response
    const firstPageExpenses = mockExpenses.slice(0, 2)
    const secondPageExpenses = [mockExpenses[2]]

    mockGetExpenses.mockResolvedValueOnce({
      data: firstPageExpenses,
      total: 3,
      page: 1,
      pages: 2
    }).mockResolvedValueOnce({
      data: secondPageExpenses,
      total: 3,
      page: 2,
      pages: 2
    })

    render(<MockedDashboard />)

    // Wait for first page to load
    await waitFor(() => {
      expect(screen.getByText('Lunch at restaurant')).toBeInTheDocument()
      expect(screen.getByText('Bus ticket')).toBeInTheDocument()
    })

    // Should not show third expense yet
    expect(screen.queryByText('Movie ticket')).not.toBeInTheDocument()

    // Click next page
    const nextButton = screen.getByLabelText(/next page|page 2/i)
    await user.click(nextButton)

    // Wait for second page to load
    await waitFor(() => {
      expect(screen.getByText('Movie ticket')).toBeInTheDocument()
    })

    // First page items should not be visible
    expect(screen.queryByText('Lunch at restaurant')).not.toBeInTheDocument()
    expect(screen.queryByText('Bus ticket')).not.toBeInTheDocument()
  })

  it('should handle API errors gracefully', async () => {
    mockGetExpenses.mockRejectedValue(new Error('Network error'))
    
    render(<MockedDashboard />)

    // Should show error message
    await waitFor(() => {
      expect(screen.getByText(/error loading expenses/i)).toBeInTheDocument()
    })
  })

  it('should show empty state when no expenses exist', async () => {
    mockGetExpenses.mockResolvedValue({
      data: [],
      total: 0,
      page: 1,
      pages: 0
    })

    render(<MockedDashboard />)

    await waitFor(() => {
      expect(screen.getByText(/no expenses found/i)).toBeInTheDocument()
    })
  })
})
