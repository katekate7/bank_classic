import { render, screen, fireEvent, waitFor } from '@testing-library/react'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { MemoryRouter } from 'react-router-dom'
import AddExpenseForm from '../src/components/AddExpenseForm'

// Mock fetch pour les tests
global.fetch = vi.fn()

describe('AddExpenseForm Integration Tests', () => {
  beforeEach(() => {
    fetch.mockClear()
    localStorage.clear()
    localStorage.setItem('token', 'mock-jwt-token')
  })

  it('should submit expense data to backend API', async () => {
    // Mock de la réponse des catégories
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ['Food', 'Transport', 'Entertainment']
    })

    // Mock de la réponse de création de dépense
    fetch.mockResolvedValueOnce({
      ok: true,
      status: 201,
      json: async () => ({ message: 'Expense created successfully', expense_id: 1 })
    })

    const mockOnSave = vi.fn()
    const mockOnCancel = vi.fn()

    render(
      <MemoryRouter>
        <AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />
      </MemoryRouter>
    )

    // Attendre que les catégories se chargent
    await waitFor(() => {
      expect(screen.getByDisplayValue('Food')).toBeInTheDocument()
    })

    // Remplir le formulaire
    fireEvent.change(screen.getByLabelText(/label/i), {
      target: { value: 'Restaurant lunch' }
    })
    fireEvent.change(screen.getByLabelText(/amount/i), {
      target: { value: '25.50' }
    })
    fireEvent.change(screen.getByLabelText(/date/i), {
      target: { value: '2024-01-15' }
    })
    fireEvent.change(screen.getByLabelText(/category/i), {
      target: { value: 'Food' }
    })

    // Soumettre le formulaire
    fireEvent.click(screen.getByRole('button', { name: /add expense/i }))

    // Vérifier que l'API a été appelée correctement
    await waitFor(() => {
      expect(fetch).toHaveBeenCalledWith(
        'http://localhost:8000/api/expense',
        expect.objectContaining({
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer mock-jwt-token'
          },
          body: JSON.stringify({
            label: 'Restaurant lunch',
            amount: 25.50,
            date: '2024-01-15',
            category: 'Food'
          })
        })
      )
    })

    // Vérifier que onSave a été appelé
    expect(mockOnSave).toHaveBeenCalled()
  })

  it('should handle API errors gracefully', async () => {
    // Mock de la réponse des catégories
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ['Food', 'Transport']
    })

    // Mock d'une erreur de l'API
    fetch.mockResolvedValueOnce({
      ok: false,
      status: 400,
      json: async () => ({ error: 'Invalid data' })
    })

    const mockOnSave = vi.fn()
    const mockOnCancel = vi.fn()

    render(
      <MemoryRouter>
        <AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />
      </MemoryRouter>
    )

    // Attendre que les catégories se chargent
    await waitFor(() => {
      expect(screen.getByDisplayValue('Food')).toBeInTheDocument()
    })

    // Remplir le formulaire avec des données invalides
    fireEvent.change(screen.getByLabelText(/label/i), {
      target: { value: '' } // Label vide
    })
    fireEvent.change(screen.getByLabelText(/amount/i), {
      target: { value: '-10' } // Montant négatif
    })

    // Soumettre le formulaire
    fireEvent.click(screen.getByRole('button', { name: /add expense/i }))

    // Vérifier qu'un message d'erreur apparaît
    await waitFor(() => {
      expect(screen.getByText(/error/i)).toBeInTheDocument()
    })

    // Vérifier que onSave n'a pas été appelé
    expect(mockOnSave).not.toHaveBeenCalled()
  })

  it('should validate form data before submission', async () => {
    // Mock de la réponse des catégories
    fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ['Food', 'Transport']
    })

    const mockOnSave = vi.fn()
    const mockOnCancel = vi.fn()

    render(
      <MemoryRouter>
        <AddExpenseForm onSave={mockOnSave} onCancel={mockOnCancel} />
      </MemoryRouter>
    )

    // Attendre que les catégories se chargent
    await waitFor(() => {
      expect(screen.getByDisplayValue('Food')).toBeInTheDocument()
    })

    // Essayer de soumettre le formulaire vide
    fireEvent.click(screen.getByRole('button', { name: /add expense/i }))

    // Vérifier que la validation côté client empêche la soumission
    expect(fetch).toHaveBeenCalledTimes(1) // Seulement l'appel pour les catégories
    expect(mockOnSave).not.toHaveBeenCalled()
  })
})
