# Tests d'Intégration - Banking Application

## 📋 Vue d'ensemble

Les tests d'intégration vérifient que **toutes les parties de l'application fonctionnent bien ensemble** : frontend, backend, et base de données. Ils simulent des scénarios réels d'utilisation pour s'assurer que l'intégration entre les composants est robuste.

## 🔗 Qu'est-ce qu'un Test d'Intégration ?

Les tests d'intégration **vont au-delà des tests unitaires** en validant :

- ✅ **Communication Frontend ↔ Backend** via API REST
- ✅ **Intégration Backend ↔ Base de données** avec Doctrine ORM  
- ✅ **Flux complets de données** de l'interface à la persistance
- ✅ **Authentification et autorisation** entre services
- ✅ **Gestion des erreurs** cross-composants

## 🏗️ Architecture d'Intégration

```
┌─────────────────┐    HTTP/JSON    ┌─────────────────┐    SQL/ORM    ┌─────────────────┐
│                 │  ============>  │                 │  ==========>  │                 │
│   React Frontend│                 │ Symfony Backend │               │  MySQL Database │
│                 │  <============  │                 │  <========== │                 │
└─────────────────┘    Responses    └─────────────────┘    Results    └─────────────────┘

Tests d'intégration = Validation de TOUS ces flux
```

## 🧪 Types de Tests d'Intégration

### 1. **Tests API-Database** 
Vérifient que les endpoints API interagissent correctement avec la base de données.

```php
// Exemple : Test d'intégration création d'expense
public function testCreateExpenseIntegration(): void
{
    // ARRANGE: Préparer les données et l'utilisateur
    $user = $this->createTestUser();
    $this->client->loginUser($user);
    
    // ACT: Appeler l'API pour créer une dépense
    $this->client->request('POST', '/api/expenses', [
        'json' => [
            'amount' => 100.50,
            'description' => 'Test expense',
            'category_id' => 1
        ]
    ]);
    
    // ASSERT: Vérifier la réponse ET la persistance en base
    $this->assertResponseStatusCodeSame(201);
    
    // Vérification dans la base de données
    $expense = $this->entityManager
        ->getRepository(Expense::class)
        ->findOneBy(['description' => 'Test expense']);
        
    $this->assertNotNull($expense);
    $this->assertEquals(100.50, $expense->getAmount());
    $this->assertEquals($user->getId(), $expense->getUser()->getId());
}
```

### 2. **Tests Frontend-Backend**
Vérifient l'intégration complète depuis l'interface utilisateur jusqu'à la base de données.

```javascript
// Exemple : Test d'intégration ajout d'expense depuis le frontend
describe('Expense Integration Flow', () => {
  test('should create expense through complete flow', async () => {
    // ARRANGE: Monter le composant avec un utilisateur connecté
    const user = await createTestUser();
    render(<ExpenseForm />, { wrapper: AuthProvider });
    
    // ACT: Simuler la saisie utilisateur
    await userEvent.type(screen.getByLabelText(/montant/i), '150.75');
    await userEvent.type(screen.getByLabelText(/description/i), 'Intégration test');
    await userEvent.selectOptions(screen.getByLabelText(/catégorie/i), 'transport');
    await userEvent.click(screen.getByRole('button', { name: /ajouter/i }));
    
    // ASSERT: Vérifier que l'expense apparaît dans la liste
    await waitFor(() => {
      expect(screen.getByText('Intégration test')).toBeInTheDocument();
      expect(screen.getByText('150.75 €')).toBeInTheDocument();
    });
    
    // Vérifier que l'API a bien été appelée
    expect(mockApiCall).toHaveBeenCalledWith('/api/expenses', {
      method: 'POST',
      body: expect.objectContaining({
        amount: 150.75,
        description: 'Intégration test'
      })
    });
  });
});
```

### 3. **Tests de Bout en Bout (E2E)**
Testent des parcours utilisateur complets dans un environnement proche de la production.

```javascript
// Exemple : Test E2E complet de gestion d'expenses
test('Complete expense management workflow', async ({ page }) => {
  // ARRANGE: Se connecter
  await page.goto('/login');
  await page.fill('[data-testid="email"]', 'test@example.com');
  await page.fill('[data-testid="password"]', 'password');
  await page.click('[data-testid="login-button"]');
  
  // ACT 1: Créer une nouvelle expense
  await page.click('[data-testid="add-expense"]');
  await page.fill('[data-testid="amount"]', '89.99');
  await page.fill('[data-testid="description"]', 'Restaurant E2E Test');
  await page.selectOption('[data-testid="category"]', 'alimentation');
  await page.click('[data-testid="save-expense"]');
  
  // ASSERT 1: Vérifier que l'expense apparaît
  await expect(page.locator('[data-testid="expense-list"]')).toContainText('Restaurant E2E Test');
  
  // ACT 2: Modifier l'expense
  await page.click('[data-testid="edit-expense"]');
  await page.fill('[data-testid="amount"]', '95.50');
  await page.click('[data-testid="save-expense"]');
  
  // ASSERT 2: Vérifier la modification
  await expect(page.locator('[data-testid="expense-amount"]')).toContainText('95.50');
  
  // ACT 3: Supprimer l'expense
  await page.click('[data-testid="delete-expense"]');
  await page.click('[data-testid="confirm-delete"]');
  
  // ASSERT 3: Vérifier la suppression
  await expect(page.locator('[data-testid="expense-list"]')).not.toContainText('Restaurant E2E Test');
});
```

## 🔧 Configuration des Tests d'Intégration

### Base de données de test
```yaml
# docker-compose.test.yml
version: '3.8'
services:
  mysql-test:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: test
      MYSQL_DATABASE: bank_test
    ports:
      - "3307:3306"
    command: --default-authentication-plugin=mysql_native_password
```

### Configuration Symfony pour les tests
```php
// config/packages/test/doctrine.yaml
doctrine:
    dbal:
        # Base de données séparée pour les tests
        url: '%env(resolve:DATABASE_TEST_URL)%'
    orm:
        # Configuration optimisée pour les tests
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
```

### Fixtures pour les tests
```php
// src/DataFixtures/TestFixtures.php
class TestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer des données de test reproductibles
        $user = new User();
        $user->setEmail('test@integration.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        
        $category = new Category();
        $category->setName('Test Category');
        
        $expense = new Expense();
        $expense->setAmount(100.00);
        $expense->setDescription('Integration Test Expense');
        $expense->setUser($user);
        $expense->setCategory($category);
        
        $manager->persist($user);
        $manager->persist($category);
        $manager->persist($expense);
        $manager->flush();
    }
}
```

## 🚀 Exécution des Tests d'Intégration

### Script d'exécution automatisé

```bash
#!/bin/bash
# run-integration-tests.sh

echo "🔧 Préparation de l'environnement de test..."

# 1. Démarrer les services de test
docker-compose -f docker-compose.test.yml up -d mysql-test

# 2. Attendre que MySQL soit prêt
echo "⏳ Attente du démarrage de MySQL..."
while ! docker-compose -f docker-compose.test.yml exec mysql-test mysql -u root -ptest -e "SELECT 1" >/dev/null 2>&1; do
    sleep 2
done

# 3. Préparer la base de données de test
echo "📦 Configuration de la base de données de test..."
export DATABASE_URL="mysql://root:test@localhost:3307/bank_test"
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:fixtures:load --env=test --no-interaction

# 4. Lancer les tests d'intégration backend
echo "🧪 Exécution des tests d'intégration backend..."
php bin/phpunit tests/Integration/ --testdox

# 5. Démarrer l'application pour les tests frontend
echo "🚀 Démarrage de l'application pour les tests frontend..."
docker-compose -f docker-compose.test.yml up -d bank-backend bank-frontend

# 6. Attendre que l'application soit prête
echo "⏳ Attente du démarrage de l'application..."
while ! curl -f http://localhost:8000/api/health >/dev/null 2>&1; do
    sleep 2
done

# 7. Lancer les tests d'intégration frontend
echo "⚛️ Exécution des tests d'intégration frontend..."
cd bank-frontend
npm run test:integration

# 8. Lancer les tests E2E
echo "🎭 Exécution des tests End-to-End..."
npm run test:e2e

# 9. Nettoyage
echo "🧹 Nettoyage de l'environnement de test..."
docker-compose -f docker-compose.test.yml down

echo "✅ Tests d'intégration terminés avec succès!"
```

### Tests en CI/CD

```yaml
# .github/workflows/integration-tests.yml
name: Integration Tests

on: [push, pull_request]

jobs:
  integration-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: test
          MYSQL_DATABASE: bank_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - name: Checkout code
        uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql
          coverage: xdebug

      - name: Install backend dependencies
        run: |
          cd bank-backend
          composer install --prefer-dist --no-progress

      - name: Setup database
        run: |
          cd bank-backend
          php bin/console doctrine:database:create --env=test
          php bin/console doctrine:migrations:migrate --env=test --no-interaction
          php bin/console doctrine:fixtures:load --env=test --no-interaction

      - name: Run integration tests
        run: |
          cd bank-backend
          php bin/phpunit tests/Integration/ --coverage-clover coverage.xml

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
          cache-dependency-path: bank-frontend/package-lock.json

      - name: Install frontend dependencies
        run: |
          cd bank-frontend
          npm ci

      - name: Start application for E2E tests
        run: |
          docker-compose -f docker-compose.test.yml up -d
          # Attendre que l'application soit prête
          timeout 60 bash -c 'until curl -f http://localhost:8000/api/health; do sleep 2; done'

      - name: Run E2E tests
        run: |
          cd bank-frontend
          npm run test:e2e

      - name: Upload coverage reports
        uses: codecov/codecov-action@v3
        with:
          file: ./bank-backend/coverage.xml
```

## 📊 Rapports et Métriques

### Métriques d'intégration surveillées

```javascript
// Exemple de rapport d'intégration
const integrationMetrics = {
  database_operations: {
    create_expense: { avg_time: '45ms', success_rate: '99.2%' },
    read_expenses: { avg_time: '23ms', success_rate: '99.8%' },
    update_expense: { avg_time: '38ms', success_rate: '98.9%' },
    delete_expense: { avg_time: '31ms', success_rate: '99.5%' }
  },
  api_integration: {
    response_times: { p50: '120ms', p95: '340ms', p99: '780ms' },
    error_rate: '0.8%',
    throughput: '150 req/sec'
  },
  frontend_backend: {
    data_consistency: '99.6%',
    real_time_updates: '98.2%',
    offline_sync: '96.8%'
  }
};
```

### Tableau de bord des tests
```bash
# Génération du rapport de tests d'intégration
./generate-integration-report.sh

# Résultat exemple :
# ✅ Database Integration: 45/45 tests passed
# ✅ API Integration: 32/32 tests passed  
# ✅ Frontend Integration: 28/28 tests passed
# ✅ E2E Scenarios: 15/15 tests passed
# 
# Total Coverage: 94.2%
# Performance: All endpoints < 200ms
# Data Integrity: 99.8% consistency
```

## 🛠️ Outils d'Intégration

### Backend
- **Symfony WebTestCase**: Tests d'intégration API
- **Doctrine Fixtures**: Données de test consistantes
- **PHPUnit Database Extension**: Tests avec base de données
- **Symfony HTTP Client**: Tests d'API externes

### Frontend  
- **React Testing Library**: Tests d'intégration composants
- **Mock Service Worker**: Simulation d'API
- **Playwright**: Tests E2E cross-browser
- **Testing Utilities**: Helpers pour tests d'intégration

### DevOps
- **Docker Compose**: Environnements de test isolés
- **GitHub Actions**: CI/CD automatisé
- **Test Containers**: Bases de données temporaires
- **Monitoring**: Métriques de performance

## 🎯 Bonnes Pratiques

### 1. **Isolation des Tests**
```php
// Chaque test d'intégration doit être indépendant
protected function setUp(): void
{
    parent::setUp();
    
    // Transaction pour isolation
    $this->entityManager->beginTransaction();
}

protected function tearDown(): void
{
    // Rollback automatique
    $this->entityManager->rollback();
    parent::tearDown();
}
```

### 2. **Données de Test Reproductibles**
```php
// Utiliser des factories pour des données cohérentes
class ExpenseFactory
{
    public static function create(array $attributes = []): Expense
    {
        return new Expense(
            amount: $attributes['amount'] ?? 100.00,
            description: $attributes['description'] ?? 'Test Expense',
            createdAt: $attributes['createdAt'] ?? new DateTime()
        );
    }
}
```

### 3. **Tests de Performance Intégrés**
```php
public function testExpenseListPerformance(): void
{
    $startTime = microtime(true);
    
    $this->client->request('GET', '/api/expenses');
    
    $executionTime = (microtime(true) - $startTime) * 1000;
    
    $this->assertResponseIsSuccessful();
    $this->assertLessThan(200, $executionTime, 'API response too slow');
}
```

## 📚 Exemples Pratiques

### Cas d'usage : Création d'expense avec validation

```php
public function testCreateExpenseWithValidation(): void
{
    // Test d'intégration complet : validation + persistance + réponse
    
    $user = $this->createTestUser();
    $this->client->loginUser($user);
    
    // Test avec données invalides
    $this->client->request('POST', '/api/expenses', [
        'json' => [
            'amount' => -100, // Montant négatif invalide
            'description' => '', // Description vide invalide
        ]
    ]);
    
    $this->assertResponseStatusCodeSame(400);
    $this->assertJsonContains([
        'errors' => [
            'amount' => 'Le montant doit être positif',
            'description' => 'La description est obligatoire'
        ]
    ]);
    
    // Vérifier qu'aucune expense n'a été créée
    $expenseCount = $this->entityManager
        ->getRepository(Expense::class)
        ->count(['user' => $user]);
    $this->assertEquals(0, $expenseCount);
    
    // Test avec données valides
    $this->client->request('POST', '/api/expenses', [
        'json' => [
            'amount' => 150.75,
            'description' => 'Expense valide',
            'category_id' => 1
        ]
    ]);
    
    $this->assertResponseStatusCodeSame(201);
    
    // Vérifier la persistance
    $expense = $this->entityManager
        ->getRepository(Expense::class)
        ->findOneBy(['description' => 'Expense valide']);
        
    $this->assertNotNull($expense);
    $this->assertEquals(150.75, $expense->getAmount());
}
```

## 🚨 Résolution de Problèmes

### Problèmes courants d'intégration

```bash
# 1. Base de données non synchronisée
php bin/console doctrine:schema:validate --env=test

# 2. Fixtures corrompues
php bin/console doctrine:fixtures:load --env=test --purge-with-truncate

# 3. Cache de test obsolète  
php bin/console cache:clear --env=test

# 4. Services Docker non démarrés
docker-compose -f docker-compose.test.yml ps
docker-compose -f docker-compose.test.yml logs
```

### Debug des tests d'intégration
```php
// Activer le mode debug pour les tests d'intégration
public function testDebugIntegration(): void
{
    $this->client->enableProfiler(); // Activer le profiler Symfony
    
    $this->client->request('POST', '/api/expenses', ['json' => $data]);
    
    // Analyser les requêtes SQL
    $profile = $this->client->getProfile();
    $collector = $profile->getCollector('db');
    
    echo "Queries executed: " . $collector->getQueryCount() . "\n";
    foreach ($collector->getQueries() as $query) {
        echo "SQL: " . $query['sql'] . "\n";
    }
}
```

## 📞 Support

### Documentation
- **Guide complet**: [README.md](./README.md)
- **Configuration CI/CD**: [CI-CD-DOCUMENTATION.md](./CI-CD-DOCUMENTATION.md)  
- **Tests généraux**: [TESTING.md](./TESTING.md)

### Contact
- **Repository**: https://github.com/katekate7/bank_classic
- **Issues**: [GitHub Issues pour problèmes d'intégration]
- **Wiki**: [Documentation technique détaillée]

---

> **💡 Les tests d'intégration sont essentiels** pour s'assurer que votre application fonctionne comme un ensemble cohérent. Ils détectent les problèmes que les tests unitaires ne peuvent pas voir et donnent confiance dans la robustesse de votre architecture.
