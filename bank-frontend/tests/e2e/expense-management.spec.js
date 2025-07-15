import { test, expect } from '@playwright/test'

/**
 * Tests End-to-End pour l'application de gestion des dépenses
 * Ces tests vérifient le fonctionnement complet de l'application
 * depuis l'interface utilisateur jusqu'à la base de données
 */

test.describe('Expense Management E2E Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Configuration de base pour chaque test
    await page.goto('http://localhost:3000')
  })

  test('Complete user journey: Register → Login → Add Expense → View → Delete', async ({ page }) => {
    const userEmail = `test-${Date.now()}@example.com`
    const userPassword = 'password123'

    // 1. Inscription d'un nouvel utilisateur
    await page.click('text=Register')
    await page.fill('input[type="email"]', userEmail)
    await page.fill('input[type="password"]', userPassword)
    await page.click('button:has-text("Register")')

    // Vérifier la redirection vers la page de connexion
    await expect(page).toHaveURL(/.*login.*/)
    await expect(page.locator('text=Account created successfully')).toBeVisible()

    // 2. Connexion avec le nouveau compte
    await page.fill('input[type="email"]', userEmail)
    await page.fill('input[type="password"]', userPassword)
    await page.click('button:has-text("Login")')

    // Vérifier la redirection vers le dashboard
    await expect(page).toHaveURL(/.*dashboard.*/)
    await expect(page.locator('text=Welcome')).toBeVisible()

    // 3. Ajouter une nouvelle dépense
    await page.click('text=Add Expense')
    await page.fill('input[name="label"]', 'Test Restaurant')
    await page.fill('input[name="amount"]', '25.50')
    await page.fill('input[name="date"]', '2024-01-15')
    await page.selectOption('select[name="category"]', 'Food')
    await page.click('button:has-text("Add Expense")')

    // Vérifier que la dépense apparaît dans la liste
    await expect(page).toHaveURL(/.*dashboard.*/)
    await expect(page.locator('text=Test Restaurant')).toBeVisible()
    await expect(page.locator('text=25.5 €')).toBeVisible()
    await expect(page.locator('text=Food')).toBeVisible()

    // 4. Modifier la dépense
    await page.click('button[aria-label="Edit expense"]')
    await page.fill('input[name="label"]', 'Updated Restaurant')
    await page.fill('input[name="amount"]', '30.00')
    await page.click('button:has-text("Save Changes")')

    // Vérifier les modifications
    await expect(page.locator('text=Updated Restaurant')).toBeVisible()
    await expect(page.locator('text=30 €')).toBeVisible()

    // 5. Supprimer la dépense
    await page.click('button[aria-label="Delete expense"]')
    await page.click('button:has-text("Confirm Delete")')

    // Vérifier que la dépense a été supprimée
    await expect(page.locator('text=Updated Restaurant')).not.toBeVisible()
    await expect(page.locator('text=No expenses found')).toBeVisible()
  })

  test('User cannot access other users data', async ({ page, context }) => {
    // Créer deux utilisateurs différents
    const user1Email = `user1-${Date.now()}@example.com`
    const user2Email = `user2-${Date.now()}@example.com`
    const password = 'password123'

    // Inscription et connexion du premier utilisateur
    await page.goto('http://localhost:3000/register')
    await page.fill('input[type="email"]', user1Email)
    await page.fill('input[type="password"]', password)
    await page.click('button:has-text("Register")')

    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', user1Email)
    await page.fill('input[type="password"]', password)
    await page.click('button:has-text("Login")')

    // Ajouter une dépense pour le premier utilisateur
    await page.click('text=Add Expense')
    await page.fill('input[name="label"]', 'User1 Private Expense')
    await page.fill('input[name="amount"]', '100.00')
    await page.fill('input[name="date"]', '2024-01-15')
    await page.selectOption('select[name="category"]', 'Personal')
    await page.click('button:has-text("Add Expense")')

    // Déconnexion
    await page.click('text=Logout')

    // Inscription et connexion du deuxième utilisateur
    await page.goto('http://localhost:3000/register')
    await page.fill('input[type="email"]', user2Email)
    await page.fill('input[type="password"]', password)
    await page.click('button:has-text("Register")')

    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', user2Email)
    await page.fill('input[type="password"]', password)
    await page.click('button:has-text("Login")')

    // Vérifier que le deuxième utilisateur ne voit pas les dépenses du premier
    await expect(page.locator('text=User1 Private Expense')).not.toBeVisible()
    await expect(page.locator('text=No expenses found')).toBeVisible()
  })

  test('Application handles network errors gracefully', async ({ page }) => {
    // Simulation d'une panne réseau
    await page.route('**/api/**', route => route.abort())

    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'test@example.com')
    await page.fill('input[type="password"]', 'password123')
    await page.click('button:has-text("Login")')

    // Vérifier qu'un message d'erreur approprié apparaît
    await expect(page.locator('text=Network error')).toBeVisible()
  })

  test('Form validation works correctly', async ({ page }) => {
    // Connexion avec un utilisateur existant
    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', 'existing@example.com')
    await page.fill('input[type="password"]', 'password123')
    await page.click('button:has-text("Login")')

    // Aller à la page d'ajout de dépense
    await page.click('text=Add Expense')

    // Test : soumission avec des champs vides
    await page.click('button:has-text("Add Expense")')
    await expect(page.locator('text=Label is required')).toBeVisible()
    await expect(page.locator('text=Amount is required')).toBeVisible()

    // Test : montant négatif
    await page.fill('input[name="label"]', 'Test')
    await page.fill('input[name="amount"]', '-10')
    await page.click('button:has-text("Add Expense")')
    await expect(page.locator('text=Amount must be positive')).toBeVisible()

    // Test : date future
    const futureDate = new Date()
    futureDate.setFullYear(futureDate.getFullYear() + 1)
    await page.fill('input[name="date"]', futureDate.toISOString().split('T')[0])
    await page.click('button:has-text("Add Expense")')
    await expect(page.locator('text=Date cannot be in the future')).toBeVisible()
  })

  test('Responsive design works on mobile devices', async ({ page }) => {
    // Simuler un appareil mobile
    await page.setViewportSize({ width: 375, height: 667 })

    await page.goto('http://localhost:3000')

    // Vérifier que l'interface s'adapte au mobile
    await expect(page.locator('.mobile-menu')).toBeVisible()
    
    // Vérifier que le menu de navigation fonctionne sur mobile
    await page.click('.mobile-menu-button')
    await expect(page.locator('.mobile-nav')).toBeVisible()
  })

  test('Application persists user session across page reloads', async ({ page }) => {
    const userEmail = `session-test-${Date.now()}@example.com`
    const password = 'password123'

    // Inscription et connexion
    await page.goto('http://localhost:3000/register')
    await page.fill('input[type="email"]', userEmail)
    await page.fill('input[type="password"]', password)
    await page.click('button:has-text("Register")')

    await page.goto('http://localhost:3000/login')
    await page.fill('input[type="email"]', userEmail)
    await page.fill('input[type="password"]', password)
    await page.click('button:has-text("Login")')

    // Vérifier que l'utilisateur est connecté
    await expect(page.locator('text=Dashboard')).toBeVisible()

    // Recharger la page
    await page.reload()

    // Vérifier que l'utilisateur est toujours connecté
    await expect(page.locator('text=Dashboard')).toBeVisible()
    await expect(page).not.toHaveURL(/.*login.*/)
  })

  test('Real-time data sync between multiple browser tabs', async ({ browser }) => {
    // Créer deux onglets pour simuler une utilisation multi-onglets
    const context = await browser.newContext()
    const page1 = await context.newPage()
    const page2 = await context.newPage()

    const userEmail = `multitab-${Date.now()}@example.com`
    const password = 'password123'

    // Connexion dans le premier onglet
    await page1.goto('http://localhost:3000/login')
    await page1.fill('input[type="email"]', userEmail)
    await page1.fill('input[type="password"]', password)
    await page1.click('button:has-text("Login")')

    // Connexion dans le deuxième onglet
    await page2.goto('http://localhost:3000/login')
    await page2.fill('input[type="email"]', userEmail)
    await page2.fill('input[type="password"]', password)
    await page2.click('button:has-text("Login")')

    // Ajouter une dépense dans le premier onglet
    await page1.click('text=Add Expense')
    await page1.fill('input[name="label"]', 'Multi-tab test expense')
    await page1.fill('input[name="amount"]', '15.00')
    await page1.fill('input[name="date"]', '2024-01-15')
    await page1.selectOption('select[name="category"]', 'Test')
    await page1.click('button:has-text("Add Expense")')

    // Actualiser le deuxième onglet et vérifier que la dépense apparaît
    await page2.reload()
    await expect(page2.locator('text=Multi-tab test expense')).toBeVisible()

    await context.close()
  })
})
