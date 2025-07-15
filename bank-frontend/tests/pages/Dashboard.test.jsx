import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { MemoryRouter } from 'react-router-dom'
import Dashboard from '../src/pages/Dashboard'

// Mock fetch pour les tests
global.fetch = vi.fn()

// Mock de react-router-dom
const mockNavigate = vi.fn()
vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual('react-router-dom')
  return {
    ...actual,
    useNavigate: () => mockNavigate
  }
})

describe('Dashboard Integration Tests', () => {
  beforeEach(() => {
    fetch.mockClear()
    mockNavigate.mockClear()
    localStorage.clear()
    localStorage.setItem('token', 'mock-jwt-token')
  })

  it('should load and display expenses from backend', async () => {
    const mockExpenses = [
      {
        id: 1,
        label: 'Grocery shopping',
        amount: 45.80,
        date: '2024-01-15',
        category: { name: 'Food' }
      },
      {
        id: 2,
        label: 'Bus ticket',
        amount: 2.50,
        date: '2024-01-14',
        category: { name: 'Transport' }
      }
    ]

    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockExpenses
    })

    render(
      <MemoryRouter>
        <Dashboard />
      </MemoryRouter>
    )

    // Vérifier que l'indicateur de chargement apparaît
    expect(screen.getByText(/loading/i)).toBeInTheDocument()

    // Attendre que les dépenses se chargent
    await waitFor(() => {
      expect(screen.getByText('Grocery shopping')).toBeInTheDocument()
      expect(screen.getByText('Bus ticket')).toBeInTheDocument()
      expect(screen.getByText('45.8 €')).toBeInTheDocument()
      expect(screen.getByText('2.5 €')).toBeInTheDocument()
    })

    // Vérifier que l'API a été appelée correctement
    expect(fetch).toHaveBeenCalledWith(
      'http://localhost:8000/api/expenses',
      expect.objectContaining({
        headers: {
          'Authorization': 'Bearer mock-jwt-token'
        }
      })
    )
  })

  it('should handle expense deletion through API', async () => {
    const mockExpenses = [
      {
        id: 1,
        label: 'Coffee',
        amount: 3.50,
        date: '2024-01-15',
        category: { name: 'Food' }
      }
    ]

    // Mock de la réponse initiale
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockExpenses
    })

    // Mock de la suppression
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({ message: 'Expense deleted successfully' })
    })

    // Mock de la réponse après suppression (liste vide)
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => []
    })

    render(
      <MemoryRouter>
        <Dashboard />
      </MemoryRouter>
    )

    // Attendre que la dépense se charge
    await waitFor(() => {
      expect(screen.getByText('Coffee')).toBeInTheDocument()
    })

    // Cliquer sur le bouton de suppression
    const deleteButton = screen.getByText('❌')
    fireEvent.click(deleteButton)

    // Vérifier que l'API de suppression a été appelée
    await waitFor(() => {
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8000/api/expense/1',
        expect.objectContaining({
          method: 'DELETE',
          headers: {
            'Authorization': 'Bearer mock-jwt-token'
          }
        })
      )
    })

    // Vérifier que la liste se recharge
    await waitFor(() => {
      expect(screen.queryByText('Coffee')).not.toBeInTheDocument()
    })
  })

  it('should handle API errors gracefully', async () => {
    // Mock d'une erreur de l'API
    fetch.mockRejectedValueOnce(new Error('Network error'))

    render(
      <MemoryRouter>
        <Dashboard />
      </MemoryRouter>
    )

    // Vérifier qu'un message d'erreur apparaît
    await waitFor(() => {
      expect(screen.getByText(/error/i)).toBeInTheDocument()
    })
  })

  it('should handle authentication errors and redirect to login', async () => {
    // Mock d'une réponse 401 (non autorisé)
    fetch.mockResolvedValueOnce({
      ok: false,
      status: 401,
      json: async () => ({ error: 'Unauthorized' })
    })

    render(
      <MemoryRouter>
        <Dashboard />
      </MemoryRouter>
    )

    // Vérifier que l'utilisateur est redirigé vers la page de connexion
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith('/login')
    })

    // Vérifier que le token est supprimé
    expect(localStorage.getItem('token')).toBeNull()
  })

  it('should navigate to add expense page when button is clicked', async () => {
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => []
    })

    render(
      <MemoryRouter>
        <Dashboard />
      </MemoryRouter>
    )

    // Attendre que la page se charge
    await waitFor(() => {
      expect(screen.getByText(/add expense/i)).toBeInTheDocument()
    })

    // Cliquer sur le bouton d'ajout
    fireEvent.click(screen.getByText(/add expense/i))

    // Vérifier la navigation
    expect(mockNavigate).toHaveBeenCalledWith('/user/expense/new')
  })

  it('should display empty state when no expenses exist', async () => {
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => []
    })

    render(
      <MemoryRouter>
        <Dashboard />
      </MemoryRouter>
    )

    // Attendre que la page se charge
    await waitFor(() => {
      expect(screen.getByText(/no expenses/i)).toBeInTheDocument()
    })
  })
})
