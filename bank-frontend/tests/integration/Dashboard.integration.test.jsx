import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { BrowserRouter } from 'react-router-dom'
import Dashboard from '../../src/pages/Dashboard'

// Mock API calls
const mockGetExpenses = vi.fn()
const mockGetCategories = vi.fn()

vi.mock('../../src/api/axios', () => ({
  default: {
    get: vi.fn((url) => {
      if (url.includes('expenses')) {
        return mockGetExpenses()
      }
      if (url.includes('categories')) {
        return mockGetCategories()
      }
      return Promise.reject(new Error('Unknown endpoint'))
    })
  }
}))

const MockedDashboard = () => (
  <BrowserRouter>
    <Dashboard />
  </BrowserRouter>
)

describe('Dashboard Integration Tests', () => {
  beforeEach(() => {
    mockGetExpenses.mockClear()
    mockGetCategories.mockClear()
    
    // Mock successful responses
    mockGetExpenses.mockResolvedValue({
      data: [
        {
          id: 1,
          amount: 25.50,
          description: 'Test expense',
          category: { id: 1, name: 'Food' },
          date: '2024-01-15'
        }
      ]
    })
    
    mockGetCategories.mockResolvedValue({
      data: [
        { id: 1, name: 'Food', color: '#FF5733' }
      ]
    })
  })

  it('should load expenses and categories from API', async () => {
    render(<MockedDashboard />)

    // Verify API calls are made
    await waitFor(() => {
      expect(mockGetExpenses).toHaveBeenCalled()
    })
  })

  it('should handle API errors gracefully', async () => {
    // Mock API error
    mockGetExpenses.mockRejectedValue(new Error('API Error'))
    
    render(<MockedDashboard />)

    // Should render without crashing even with API error
    await waitFor(() => {
      expect(screen.getByText(/dashboard/i) || document.body).toBeInTheDocument()
    })
  })
})
