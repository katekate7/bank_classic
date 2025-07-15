import { render, screen } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import LoginForm from '../src/components/LoginForm'
import { MemoryRouter } from 'react-router-dom'

// Mock localStorage for authenticated state
beforeEach(() => {
  localStorage.clear()
})

describe('App Component', () => {
  it('renders login form without crashing', () => {
    render(
      <MemoryRouter>
        <LoginForm setAuthenticated={() => {}} />
      </MemoryRouter>
    )
    expect(screen.getByText('Welcome to MyBank')).toBeInTheDocument()
  })

  it('shows login button in form', () => {
    render(
      <MemoryRouter>
        <LoginForm setAuthenticated={() => {}} />
      </MemoryRouter>
    )
    expect(screen.getByRole('button', { name: /login/i })).toBeInTheDocument()
  })
})
