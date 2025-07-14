import { render, screen, waitFor } from '@testing-library/react'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import userEvent from '@testing-library/user-event'
import Dashboard from '../pages/Dashboard'
import * as api from '../api/axios'

// Mock the API
vi.mock('../api/axios', () => ({
  default: {
    get: vi.fn(),
    delete: vi.fn(),
  }
}))

// Mock window.location
const mockLocation = {
  href: '',
}
Object.defineProperty(window, 'location', {
  value: mockLocation,
  writable: true,
})

// Mock localStorage
const localStorageMock = {
  removeItem: vi.fn(),
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
  writable: true,
})

describe('Dashboard', () => {
  const mockSetAuthenticated = vi.fn()
  
  const mockExpenses = [
    {
      id: 1,
      label: 'Coffee',
      amount: 3.50,
      date: '2025-01-15T08:00:00Z',
      category: { name: 'Food' }
    },
    {
      id: 2,
      label: 'Bus Ticket',
      amount: 2.25,
      date: '2025-01-14T07:30:00Z',
      category: { name: 'Transportation' }
    }
  ]

  beforeEach(() => {
    vi.clearAllMocks()
    mockLocation.href = ''
    api.default.get.mockResolvedValue({ data: mockExpenses })
    api.default.delete.mockResolvedValue({})
  })

  it('renders dashboard title and elements', async () => {
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    expect(screen.getByText('Dashboard')).toBeInTheDocument()
    expect(screen.getByText('Add Expense')).toBeInTheDocument()
    expect(screen.getByText('Log out')).toBeInTheDocument()
    expect(screen.getByAltText('Add Expense')).toBeInTheDocument()
  })

  it('fetches and displays expenses on mount', async () => {
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    await waitFor(() => {
      expect(api.default.get).toHaveBeenCalledWith('/api/expenses', { withCredentials: true })
    })

    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
      expect(screen.getByText('Bus Ticket')).toBeInTheDocument()
    })
  })

  it('displays "No expenses yet" when no expenses exist', async () => {
    api.default.get.mockResolvedValue({ data: [] })
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    await waitFor(() => {
      expect(screen.getByText('No expenses yet.')).toBeInTheDocument()
    })
  })

  it('handles API error when fetching expenses', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    api.default.get.mockRejectedValue(new Error('API Error'))
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    await waitFor(() => {
      expect(consoleSpy).toHaveBeenCalledWith('Error fetching expenses:', expect.any(Error))
    })

    // Should still show "No expenses yet" when there's an error
    expect(screen.getByText('No expenses yet.')).toBeInTheDocument()
    
    consoleSpy.mockRestore()
  })

  it('handles logout correctly', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    await user.click(screen.getByText('Log out'))

    expect(localStorageMock.removeItem).toHaveBeenCalledWith('authenticated')
    expect(mockSetAuthenticated).toHaveBeenCalledWith(false)
  })

  it('redirects to add expense page when add button is clicked', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    // Click on the add expense button
    await user.click(screen.getByText('Add Expense'))

    expect(mockLocation.href).toBe('/user/expense/new')
  })

  it('redirects to add expense page when plus icon is clicked', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    // Click on the plus icon
    await user.click(screen.getByAltText('Add Expense'))

    expect(mockLocation.href).toBe('/user/expense/new')
  })

  it('handles expense deletion successfully', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
    })

    // Find and click delete button for the first expense
    const deleteButtons = screen.getAllByText('❌')
    await user.click(deleteButtons[0])

    await waitFor(() => {
      expect(api.default.delete).toHaveBeenCalledWith('/api/expense/1', { withCredentials: true })
    })

    // The expense should be removed from the list
    await waitFor(() => {
      expect(screen.queryByText('Coffee')).not.toBeInTheDocument()
    })
    
    // Bus Ticket should still be there
    expect(screen.getByText('Bus Ticket')).toBeInTheDocument()
  })

  it('handles expense deletion error', async () => {
    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})
    const user = userEvent.setup()
    
    api.default.delete.mockRejectedValue(new Error('Delete Error'))
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
    })

    // Find and click delete button
    const deleteButtons = screen.getAllByText('❌')
    await user.click(deleteButtons[0])

    await waitFor(() => {
      expect(consoleSpy).toHaveBeenCalledWith('Error deleting expense:', expect.any(Error))
    })

    // The expense should still be in the list after failed deletion
    expect(screen.getByText('Coffee')).toBeInTheDocument()
    
    consoleSpy.mockRestore()
  })

  it('displays expense cards with correct data', async () => {
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
      expect(screen.getByText('Bus Ticket')).toBeInTheDocument()
    })

    // Check that expense amounts are displayed
    expect(screen.getByText('Amount: 3.5 €')).toBeInTheDocument()
    expect(screen.getByText('Amount: 2.25 €')).toBeInTheDocument()
    
    // Check that categories are displayed
    expect(screen.getByText('| Food')).toBeInTheDocument()
    expect(screen.getByText('| Transportation')).toBeInTheDocument()
  })

  it('maintains expense order after deletion', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
      expect(screen.getByText('Bus Ticket')).toBeInTheDocument()
    })

    // Delete the second expense (Bus Ticket)
    const deleteButtons = screen.getAllByText('❌')
    await user.click(deleteButtons[1])

    await waitFor(() => {
      expect(api.default.delete).toHaveBeenCalledWith('/api/expense/2', { withCredentials: true })
    })

    // Only Coffee should remain
    await waitFor(() => {
      expect(screen.queryByText('Bus Ticket')).not.toBeInTheDocument()
    })
    expect(screen.getByText('Coffee')).toBeInTheDocument()
  })

  it('handles edit button navigation', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard setAuthenticated={mockSetAuthenticated} />)

    // Wait for expenses to load
    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
    })

    // Click edit button for first expense
    const editButtons = screen.getAllByText('✏️')
    await user.click(editButtons[0])

    expect(mockLocation.href).toBe('/user/expense/1/edit')
  })
})
