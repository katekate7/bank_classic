# Documentation des Tests - Banking Application

## 📋 Vue d'ensemble des Tests

L'application bancaire implémente une stratégie de test complète avec plusieurs niveaux de validation pour garantir la qualité et la fiabilité du code.

## 🏗️ Architecture de Test

### Pyramide des Tests
```
                     /\
                    /  \
                   / E2E \      <- Tests End-to-End (10%)
                  /______\
                 /        \
                /Integration\ <- Tests d'Intégration (20%)
               /____________\
              /              \
             /   Unit Tests   \  <- Tests Unitaires (70%)
            /________________\
```

## 🧪 Types de Tests Implémentés

### 1. Tests Unitaires

#### Backend (Symfony)
- **Entités**: Validation des modèles de données
- **Services**: Logique métier
- **Repositories**: Requêtes base de données
- **Controllers**: Endpoints API

```bash
# Lancer les tests unitaires backend
cd bank-backend
php bin/phpunit tests/Unit/
```

#### Frontend (React)
- **Composants**: Rendu et comportement
- **Hooks**: Logique React
- **Utils**: Fonctions utilitaires
- **Services**: API calls

```bash
# Lancer les tests unitaires frontend
cd bank-frontend
npm run test:unit
```

### 2. Tests d'Intégration

#### Backend
- **API Endpoints**: Tests complets des routes
- **Base de données**: Interactions ORM
- **Authentification**: JWT et sessions
- **Middlewares**: Validation et sécurité

```bash
# Tests d'intégration backend
php bin/phpunit tests/Integration/
```

#### Frontend
- **Navigation**: React Router
- **État global**: Context/Redux
- **API Integration**: Appels réseau
- **Formulaires**: Validation et soumission

```bash
# Tests d'intégration frontend
npm run test:integration
```

### 3. Tests End-to-End (E2E)

#### Scénarios utilisateur complets
- **Inscription/Connexion**: Parcours d'authentification
- **Gestion des dépenses**: CRUD complet
- **Catégorisation**: Organisation des données
- **Rapports**: Génération et export

```bash
# Tests E2E
npm run test:e2e
```

## 📁 Structure des Tests

### Backend (`bank-backend/tests/`)
```
tests/
├── Unit/
│   ├── Entity/
│   │   ├── UserTest.php
│   │   ├── ExpenseTest.php
│   │   └── CategoryTest.php
│   ├── Service/
│   │   ├── ExpenseServiceTest.php
│   │   └── CategoryServiceTest.php
│   └── Repository/
│       ├── ExpenseRepositoryTest.php
│       └── CategoryRepositoryTest.php
├── Integration/
│   ├── Controller/
│   │   ├── ApiExpenseControllerTest.php
│   │   └── UserExpenseControllerTest.php
│   ├── Database/
│   │   └── DatabaseIntegrationTest.php
│   └── Security/
│       └── AuthenticationTest.php
├── E2E/
│   └── ExpenseManagementE2ETest.php
└── bootstrap.php
```

### Frontend (`bank-frontend/tests/`)
```
tests/
├── unit/
│   ├── components/
│   │   ├── ExpenseForm.test.jsx
│   │   ├── ExpenseList.test.jsx
│   │   └── CategorySelect.test.jsx
│   ├── hooks/
│   │   ├── useAuth.test.js
│   │   └── useExpenses.test.js
│   └── utils/
│       ├── api.test.js
│       └── validators.test.js
├── integration/
│   ├── pages/
│   │   ├── Dashboard.test.jsx
│   │   └── ExpenseManager.test.jsx
│   └── flows/
│       └── AuthFlow.test.jsx
└── e2e/
    ├── expense-management.spec.js
    └── user-authentication.spec.js
```

## 🔧 Configuration des Tests

### PHPUnit (Backend)
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory>tests/E2E</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/DataFixtures</directory>
            <file>src/Kernel.php</file>
        </exclude>
    </coverage>
</phpunit>
```

### Vitest (Frontend)
```javascript
// vitest.config.js
import { defineConfig } from 'vitest/config'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    setupFiles: ['./tests/setup.js'],
    coverage: {
      reporter: ['text', 'html', 'lcov'],
      threshold: {
        global: {
          branches: 75,
          functions: 75,
          lines: 75,
          statements: 75
        }
      }
    }
  }
})
```

## 🚀 Exécution des Tests

### Scripts automatisés

#### Script principal (`run-tests.sh`)
```bash
#!/bin/bash
set -e

echo "🧪 Démarrage des tests complets..."

# Tests Backend
echo "📦 Tests Backend Symfony..."
cd bank-backend
php bin/phpunit --coverage-html coverage/

# Tests Frontend  
echo "⚛️ Tests Frontend React..."
cd ../bank-frontend
npm run test:coverage

# Tests E2E
echo "🎭 Tests End-to-End..."
npm run test:e2e

echo "✅ Tous les tests terminés avec succès!"
```

#### Tests spécifiques
```bash
# Tests unitaires uniquement
./run-tests.sh --unit

# Tests d'intégration uniquement  
./run-tests.sh --integration

# Tests avec couverture détaillée
./run-tests.sh --coverage

# Tests en mode watch (développement)
./run-tests.sh --watch
```

### Tests en environnement Docker
```bash
# Tests complets dans les conteneurs
docker-compose -f docker-compose.test.yml up --build

# Tests backend dans Docker
docker-compose exec bank-backend php bin/phpunit

# Tests frontend dans Docker
docker-compose exec bank-frontend npm test
```

## 📊 Rapports de Tests

### Couverture de Code

#### Backend
- **Couverture minimale**: 80%
- **Format**: HTML, XML, LCOV
- **Localisation**: `bank-backend/coverage/`

#### Frontend
- **Couverture minimale**: 75%
- **Format**: HTML, JSON, LCOV
- **Localisation**: `bank-frontend/coverage/`

### Rapports d'exécution
```bash
# Génération des rapports
php bin/phpunit --coverage-html coverage/ --log-junit junit.xml
npm run test:coverage -- --reporter=junit --outputFile=junit.xml
```

## 🐛 Tests de Régression

### Base de tests de référence
- **Golden tests**: Snapshots d'interfaces
- **Baseline performance**: Temps de réponse API
- **Database states**: États de données de référence

### Détection automatique
```bash
# Comparaison avec la baseline
npm run test:regression
php bin/phpunit --group=regression
```

## 🔒 Tests de Sécurité

### Validation des entrées
- **Injection SQL**: Protection ORM
- **XSS**: Échappement des données
- **CSRF**: Tokens de validation
- **Authentication**: JWT et sessions

### Tests de vulnérabilités
```bash
# Scan sécurité backend
symfony security:check
php bin/phpunit tests/Security/

# Audit frontend
npm audit
npm run test:security
```

## 🎯 Tests de Performance

### Métriques surveillées
- **Temps de réponse API**: < 200ms
- **Chargement des pages**: < 2s
- **Queries DB**: Optimisation requêtes
- **Bundle size**: < 500KB

### Outils de mesure
```bash
# Performance backend
php bin/phpunit tests/Performance/
ab -n 1000 -c 10 http://localhost:8000/api/expenses

# Performance frontend
npm run lighthouse
npm run bundle-analyzer
```

## 🔄 Tests dans CI/CD

### GitHub Actions
```yaml
# Tests automatiques sur chaque push
- name: Run Backend Tests
  run: |
    cd bank-backend
    php bin/phpunit --coverage-clover coverage.xml

- name: Run Frontend Tests  
  run: |
    cd bank-frontend
    npm run test:ci

- name: Upload Coverage
  uses: codecov/codecov-action@v3
```

### Conditions de passage
- ✅ Tous les tests passent
- ✅ Couverture >= seuils définis
- ✅ Pas de vulnérabilités critiques
- ✅ Performance dans les limites

## 🛠️ Outils et Frameworks

### Backend
- **PHPUnit**: Framework de test principal
- **Symfony Test Client**: Tests d'intégration
- **Faker**: Génération de données de test
- **Database Transactions**: Isolation des tests

### Frontend
- **Vitest**: Framework de test moderne
- **Testing Library**: Tests centrés utilisateur
- **Mock Service Worker**: Mocking des API
- **Playwright**: Tests E2E cross-browser

## 📚 Bonnes Pratiques

### Écriture de tests
1. **AAA Pattern**: Arrange, Act, Assert
2. **Tests indépendants**: Pas de dépendances entre tests
3. **Noms descriptifs**: Comportement attendu clair
4. **Données isolées**: Fixtures et factories
5. **Mock external services**: API tiers, email, etc.

### Organisation
1. **Un test par comportement**
2. **Tests rapides et fiables**
3. **Maintenance régulière**
4. **Documentation des cas complexes**
5. **Review des tests en PR**

## 🆘 Dépannage Tests

### Problèmes courants
```bash
# Base de données de test corrompue
php bin/console doctrine:database:drop --env=test --force
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test

# Cache de test
php bin/console cache:clear --env=test
npm run test:clear-cache

# Dépendances obsolètes
composer update --dev
npm update --dev
```

### Debug des tests
```bash
# Mode verbose
php bin/phpunit --verbose
npm run test -- --verbose

# Test spécifique
php bin/phpunit tests/Unit/Entity/ExpenseTest.php
npm run test ExpenseForm.test.jsx

# Avec debugger
php bin/phpunit --debug
npm run test:debug
```

## 📞 Support et Documentation

### Ressources
- **Wiki interne**: [Lien vers documentation]
- **Best practices**: [Guide d'équipe]
- **Troubleshooting**: [FAQ des tests]

### Contact
- **Tech Lead**: @katekate7
- **Repository**: https://github.com/katekate7/bank_classic
- **Issues**: [GitHub Issues pour bugs de tests]
