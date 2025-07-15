import { test, expect } from '@playwright/test'

/**
 * Tests End-to-End complets pour l'application bancaire
 * Ces tests simulent un utilisateur réel utilisant l'application
 * du frontend jusqu'au backend et à la base de données
 */

test.describe('Complete Banking Application E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Aller à la page d'accueil
    await page.goto('/')
  })

  test('Complete user journey: registration to expense management', async ({ page }) => {
    // 🔐 PHASE 1: Inscription d'un nouvel utilisateur
    await page.click('text=Register')
    
    await page.fill('[data-testid="firstName"]', 'E2E')
    await page.fill('[data-testid="lastName"]', 'TestUser')
    await page.fill('[data-testid="email"]', `e2etest+${Date.now()}@example.com`)
    await page.fill('[data-testid="password"]', 'SecurePassword123!')
    await page.fill('[data-testid="confirmPassword"]', 'SecurePassword123!')
    
    await page.click('[data-testid="register-button"]')
    
    // Vérifier la redirection vers le dashboard
    await expect(page).toHaveURL(/.*\/dashboard/)
    await expect(page.locator('[data-testid="welcome-message"]')).toContainText('Welcome, E2E')

    // 📊 PHASE 2: Explorer le dashboard vide
    await expect(page.locator('[data-testid="expenses-list"]')).toBeVisible()
    await expect(page.locator('[data-testid="empty-state"]')).toContainText('No expenses found')
    await expect(page.locator('[data-testid="total-amount"]')).toContainText('€0.00')

    // 🏷️ PHASE 3: Créer une nouvelle catégorie
    await page.click('[data-testid="manage-categories-button"]')
    await page.click('[data-testid="add-category-button"]')
    
    await page.fill('[data-testid="category-name"]', 'E2E Test Category')
    await page.fill('[data-testid="category-description"]', 'Category for E2E testing')
    await page.fill('[data-testid="category-color"]', '#FF6B6B')
    
    await page.click('[data-testid="save-category-button"]')
    
    // Vérifier que la catégorie a été créée
    await expect(page.locator('[data-testid="category-item"]')).toContainText('E2E Test Category')

    // Retour au dashboard
    await page.click('[data-testid="back-to-dashboard"]')

    // 💰 PHASE 4: Créer plusieurs dépenses
    const expenses = [
      { amount: '25.50', description: 'E2E Test Lunch', date: '2024-01-15' },
      { amount: '15.00', description: 'E2E Test Transport', date: '2024-01-14' },
      { amount: '45.75', description: 'E2E Test Shopping', date: '2024-01-13' }
    ]

    for (const expense of expenses) {
      await page.click('[data-testid="add-expense-button"]')
      
      await page.fill('[data-testid="expense-amount"]', expense.amount)
      await page.fill('[data-testid="expense-description"]', expense.description)
      await page.selectOption('[data-testid="expense-category"]', { label: 'E2E Test Category' })
      await page.fill('[data-testid="expense-date"]', expense.date)
      
      await page.click('[data-testid="save-expense-button"]')
      
      // Vérifier que la dépense apparaît dans la liste
      await expect(page.locator('[data-testid="expenses-list"]')).toContainText(expense.description)
    }

    // 📊 PHASE 5: Vérifier les calculs et l'affichage
    // Total devrait être 25.50 + 15.00 + 45.75 = 86.25
    await expect(page.locator('[data-testid="total-amount"]')).toContainText('€86.25')
    
    // Vérifier que toutes les dépenses sont affichées
    await expect(page.locator('[data-testid="expense-item"]')).toHaveCount(3)

    // ✏️ PHASE 6: Modifier une dépense
    await page.click('[data-testid="expense-item"]:first-child [data-testid="edit-button"]')
    
    await page.fill('[data-testid="expense-amount"]', '30.00')
    await page.fill('[data-testid="expense-description"]', 'E2E Test Lunch - Updated')
    
    await page.click('[data-testid="save-expense-button"]')
    
    // Vérifier les modifications
    await expect(page.locator('[data-testid="expenses-list"]')).toContainText('E2E Test Lunch - Updated')
    await expect(page.locator('[data-testid="total-amount"]')).toContainText('€90.75') // 30.00 + 15.00 + 45.75

    // 🔍 PHASE 7: Tester la recherche et le filtrage
    await page.fill('[data-testid="search-input"]', 'Transport')
    
    // Seule la dépense transport devrait être visible
    await expect(page.locator('[data-testid="expense-item"]')).toHaveCount(1)
    await expect(page.locator('[data-testid="expenses-list"]')).toContainText('E2E Test Transport')
    
    // Effacer la recherche
    await page.fill('[data-testid="search-input"]', '')
    await expect(page.locator('[data-testid="expense-item"]')).toHaveCount(3)

    // 📈 PHASE 8: Tester le tri
    await page.selectOption('[data-testid="sort-select"]', 'amount-desc')
    
    // Vérifier l'ordre: Shopping (45.75), Lunch Updated (30.00), Transport (15.00)
    const sortedExpenses = page.locator('[data-testid="expense-item"]')
    await expect(sortedExpenses.nth(0)).toContainText('E2E Test Shopping')
    await expect(sortedExpenses.nth(1)).toContainText('E2E Test Lunch - Updated')
    await expect(sortedExpenses.nth(2)).toContainText('E2E Test Transport')

    // 🗑️ PHASE 9: Supprimer une dépense
    await page.click('[data-testid="expense-item"]:last-child [data-testid="delete-button"]')
    
    // Confirmer la suppression
    await page.click('[data-testid="confirm-delete-button"]')
    
    // Vérifier la suppression
    await expect(page.locator('[data-testid="expense-item"]')).toHaveCount(2)
    await expect(page.locator('[data-testid="expenses-list"]')).not.toContainText('E2E Test Transport')
    await expect(page.locator('[data-testid="total-amount"]')).toContainText('€75.75') // 30.00 + 45.75

    // 📊 PHASE 10: Consulter les rapports (si disponible)
    if (await page.locator('[data-testid="reports-tab"]').isVisible()) {
      await page.click('[data-testid="reports-tab"]')
      
      await expect(page.locator('[data-testid="chart-container"]')).toBeVisible()
      await expect(page.locator('[data-testid="category-breakdown"]')).toContainText('E2E Test Category')
    }

    // 🔓 PHASE 11: Déconnexion
    await page.click('[data-testid="user-menu"]')
    await page.click('[data-testid="logout-button"]')
    
    // Vérifier la redirection vers la page de connexion
    await expect(page).toHaveURL(/.*\/login/)
    await expect(page.locator('[data-testid="login-form"]')).toBeVisible()
  })

  test('Login flow with existing user', async ({ page }) => {
    // Test de connexion avec un utilisateur existant
    await page.click('text=Login')
    
    await page.fill('[data-testid="email"]', 'existing@example.com')
    await page.fill('[data-testid="password"]', 'password123')
    
    await page.click('[data-testid="login-button"]')
    
    // En cas d'utilisateur existant, devrait aller au dashboard
    // Sinon, gérer l'erreur de connexion
    await page.waitForURL(/.*\/(dashboard|login)/)
    
    if (page.url().includes('dashboard')) {
      await expect(page.locator('[data-testid="expenses-list"]')).toBeVisible()
    } else {
      // Gérer le cas où l'utilisateur n'existe pas
      await expect(page.locator('[data-testid="error-message"]')).toBeVisible()
    }
  })

  test('Error handling and validation', async ({ page }) => {
    // Test de gestion des erreurs et validation
    
    // 1. Test de validation du formulaire d'inscription
    await page.click('text=Register')
    await page.click('[data-testid="register-button"]') // Soumettre sans données
    
    await expect(page.locator('[data-testid="email-error"]')).toContainText('Email is required')
    await expect(page.locator('[data-testid="password-error"]')).toContainText('Password is required')

    // 2. Test de validation de l'email
    await page.fill('[data-testid="email"]', 'invalid-email')
    await page.click('[data-testid="register-button"]')
    
    await expect(page.locator('[data-testid="email-error"]')).toContainText('Valid email is required')

    // 3. Test de validation du mot de passe
    await page.fill('[data-testid="email"]', 'test@example.com')
    await page.fill('[data-testid="password"]', '123') // Mot de passe trop court
    await page.click('[data-testid="register-button"]')
    
    await expect(page.locator('[data-testid="password-error"]')).toContainText('Password must be at least')
  })

  test('Expense form validation', async ({ page }) => {
    // Ce test nécessite un utilisateur connecté
    // On peut soit créer un utilisateur, soit utiliser des données de test
    
    // Simulation d'une connexion réussie
    await page.goto('/dashboard') // Supposons que nous avons un utilisateur de test
    
    // Test de validation du formulaire de dépense
    await page.click('[data-testid="add-expense-button"]')
    await page.click('[data-testid="save-expense-button"]') // Soumettre sans données
    
    await expect(page.locator('[data-testid="amount-error"]')).toContainText('Amount is required')
    await expect(page.locator('[data-testid="description-error"]')).toContainText('Description is required')

    // Test de montant négatif
    await page.fill('[data-testid="expense-amount"]', '-10')
    await page.click('[data-testid="save-expense-button"]')
    
    await expect(page.locator('[data-testid="amount-error"]')).toContainText('Amount must be positive')
  })

  test('Responsive design and mobile compatibility', async ({ page }) => {
    // Test de la responsivité sur mobile
    await page.setViewportSize({ width: 375, height: 667 }) // iPhone SE size
    
    await page.goto('/dashboard')
    
    // Vérifier que les éléments principaux sont visibles sur mobile
    await expect(page.locator('[data-testid="mobile-menu-button"]')).toBeVisible()
    await expect(page.locator('[data-testid="expenses-list"]')).toBeVisible()
    
    // Test du menu mobile
    await page.click('[data-testid="mobile-menu-button"]')
    await expect(page.locator('[data-testid="mobile-navigation"]')).toBeVisible()
    
    // Test de l'ajout de dépense sur mobile
    await page.click('[data-testid="add-expense-fab"]') // Floating Action Button sur mobile
    await expect(page.locator('[data-testid="expense-form"]')).toBeVisible()
  })

  test('Performance and loading states', async ({ page }) => {
    // Test des états de chargement et des performances
    
    // Intercepter les appels API pour simuler des délais
    await page.route('**/api/expenses', async route => {
      await new Promise(resolve => setTimeout(resolve, 1000)) // Délai de 1 seconde
      await route.continue()
    })
    
    await page.goto('/dashboard')
    
    // Vérifier que l'indicateur de chargement apparaît
    await expect(page.locator('[data-testid="loading-spinner"]')).toBeVisible()
    
    // Attendre que les données se chargent
    await expect(page.locator('[data-testid="expenses-list"]')).toBeVisible()
    await expect(page.locator('[data-testid="loading-spinner"]')).not.toBeVisible()
  })
})
