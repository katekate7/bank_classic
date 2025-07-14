# Tests d'Intégration - Banking Application

## Vue d'Ensemble

Les tests d'intégration vérifient que **toutes les parties de l'application fonctionnent bien ensemble** : 
- **Frontend** (React/Vite)
- **Backend** (Symfony/PHP) 
- **Base de données** (SQLite/Doctrine)

## Architecture des Tests d'Intégration

### 1. Tests Backend d'Intégration

#### `DatabaseTestCase` - Base Class for Integration
```php
/bank-backend/tests/Integration/DatabaseTestCase.php
```
- **Objectif** : Fournit une base pour tous les tests d'intégration qui nécessitent une vraie base de données
- **Fonctionnalités** :
  - Création automatique du schéma de base de données
  - Isolation des tests avec transactions
  - Cleanup automatique après chaque test

#### `DatabaseIntegrationTest` - Tests de Base de Données
```php
/bank-backend/tests/Integration/DatabaseIntegrationTest.php
```
- **Vérification** : Connexion et opérations CRUD avec la base de données
- **Tests** :
  - Création et persistance des entités (User, Category, Expense)
  - Relations entre entités (OneToMany, ManyToOne)
  - Opérations cascade et suppression d'orphelins

#### `UserExpenseControllerTest` - Tests Contrôleur Web
```php
/bank-backend/tests/Controller/UserExpenseControllerTest.php
```
- **Vérification** : Intégration entre frontend web et backend
- **Tests** :
  - Authentification et autorisation
  - Rendu des pages avec données de base
  - Formulaires et soumission de données
  - Redirection et gestion d'erreurs

### 2. Tests Frontend d'Intégration

#### `src/test/api.test.js` - Tests API
```javascript
/bank-frontend/src/test/api.test.js
```
- **Vérification** : Communication Frontend ↔ Backend API
- **Tests** :
  - Requêtes HTTP (GET, POST, PUT, DELETE)
  - Gestion des réponses et erreurs
  - Format des données JSON

#### `src/test/Dashboard.test.jsx` - Tests Composants
```javascript
/bank-frontend/src/test/Dashboard.test.jsx
```
- **Vérification** : Intégration des composants avec état et API
- **Tests** :
  - Chargement des données depuis l'API
  - Mise à jour de l'interface utilisateur
  - Interactions utilisateur complètes

### 3. Tests End-to-End (E2E)

#### `ExpenseManagementE2ETest` - Tests Bout en Bout
```php
/bank-backend/tests/E2E/ExpenseManagementE2ETest.php
```
- **Vérification** : Flux complet utilisateur
- **Scénarios** :
  - Connexion → Ajout expense → Modification → Suppression
  - Parcours complet dans l'application

## Configuration des Environnements de Test

### Backend Test Environment
```yaml
# docker-compose.test.yml
services:
  backend-test:
    build:
      target: test
    environment:
      DATABASE_URL: "sqlite:///:memory:"
      APP_ENV: test
```

### Frontend Test Environment
```javascript
// vitest.config.js
export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.js']
  }
})
```

### Test Database Configuration
```env
# .env.test
DATABASE_URL="sqlite:///:memory:"
APP_ENV=test
KERNEL_CLASS='App\Kernel'
```

## Procédure d'Exécution des Tests

### 1. Tests d'Intégration Automatisés
```bash
# Script principal
./run-tests.sh

# Tests backend seulement
docker-compose -f docker-compose.test.yml run --rm backend-test vendor/bin/phpunit

# Tests frontend seulement  
docker-compose -f docker-compose.test.yml run --rm frontend-test npm test
```

### 2. Tests par Catégorie
```bash
# Tests d'intégration seulement
vendor/bin/phpunit tests/Integration/

# Tests contrôleurs (intégration web)
vendor/bin/phpunit tests/Controller/

# Tests E2E
vendor/bin/phpunit tests/E2E/
```

### 3. Pipeline CI/CD
```groovy
// Jenkinsfile.test
pipeline {
    agent any
    stages {
        stage('Integration Tests') {
            steps {
                sh './run-tests.sh'
            }
        }
    }
}
```

## Types de Tests d'Intégration Implementés

### 1. Tests d'Intégration Système
- **Base de données ↔ Backend** : Vérification ORM/Doctrine
- **Backend ↔ Frontend** : Tests API REST
- **Authentification intégrée** : Sessions et sécurité

### 2. Tests d'Intégration Composants
- **Formulaires** : Validation + persistance
- **Navigation** : Routage + état application
- **État global** : Cohérence données frontend/backend

### 3. Tests d'Acceptation Client
- **Scénarios métier** : Gestion des dépenses complète
- **Expérience utilisateur** : Parcours utilisateur réels
- **Performance** : Temps de réponse et chargement

## Points de Vérification d'Intégration

### ✅ Base de Données
- [x] Schéma créé automatiquement
- [x] Relations entités fonctionnelles
- [x] Migrations compatibles
- [x] Transactions et rollback

### ✅ Backend API
- [x] Endpoints fonctionnels
- [x] Authentification sécurisée
- [x] Validation données
- [x] Gestion erreurs

### ✅ Frontend
- [x] Communication API stable
- [x] État synchronisé
- [x] Interface réactive
- [x] Navigation fluide

### ✅ Intégration Complète
- [x] Flux utilisateur bout en bout
- [x] Données persistées correctement
- [x] Synchronisation temps réel
- [x] Gestion d'erreurs globale

## Résultats Attendus

Quand tous les tests d'intégration passent, cela garantit que :

1. **L'application complète fonctionne** - frontend, backend et base de données communiquent correctement
2. **Les flux métier sont validés** - les utilisateurs peuvent accomplir leurs tâches
3. **La fiabilité est assurée** - les changements de code ne cassent pas l'intégration
4. **La qualité est maintenue** - les standards d'architecture sont respectés

Les tests d'intégration sont **essentiels** pour une application bancaire car ils valident que toutes les couches de sécurité, de persistance et d'interface fonctionnent ensemble de manière cohérente et fiable.
