# 🏦 Bank Application - Gestion des Dépenses Personnelles

Une application moderne de gestion des dépenses personnelles développée avec **Symfony** (backend) et **React** (frontend), entièrement conteneurisée avec **Docker** et intégrée dans un pipeline **CI/CD**.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4.svg)
![Symfony](https://img.shields.io/badge/Symfony-6.4-000000.svg)
![React](https://img.shields.io/badge/React-18-61DAFB.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED.svg)
![CI/CD](https://img.shields.io/badge/CI%2FCD-GitHub%20Actions## 📚 Documentation Additionnelle

- � [DEPLOYMENT.md](DEPLOYMENT.md) - Guide de déploiement détaillé
- 🧪 [TESTING-COMPLETE.md](TESTING-COMPLETE.md) - Documentation des tests
- 🔄 [CI-CD-DOCUMENTATION.md](CI-CD-DOCUMENTATION.md) - Pipeline CI/CD
- 🏗️ [INFRASTRUCTURE.md](INFRASTRUCTURE.md) - Architecture technique
- ✅ [COMPETENCIES-VALIDATION.md](COMPETENCIES-VALIDATION.md) - Validation des compétences
- 🎯 [COMPETENCIES-MAPPING.md](COMPETENCIES-MAPPING.md) - Localisation précise des compétences.svg)

## 📋 Table des Matières

- [Vue d'ensemble](#-vue-densemble)
- [Fonctionnalités](#-fonctionnalités)
- [Prérequis](#-prérequis)
- [Installation Rapide](#-installation-rapide)
- [Utilisation](#-utilisation)
- [Architecture](#-architecture)
- [Développement](#-développement)
- [Tests](#-tests)
- [Déploiement](#-déploiement)
- [API Documentation](#-api-documentation)
- [Troubleshooting](#-troubleshooting)
- [Contribution](#-contribution)

## 🎯 Vue d'ensemble

Cette application permet aux utilisateurs de :
- **Gérer leurs dépenses personnelles** avec catégorisation
- **Visualiser leurs données financières** via une interface moderne
- **Sécuriser leurs informations** avec authentification JWT
- **Accéder à leurs données** depuis n'importe quel appareil

### Technologies Utilisées

**Backend**
- 🐘 **PHP 8.2** + **Symfony 6.4**
- 🗄️ **MySQL 8.0** avec **Doctrine ORM**
- 🔐 **JWT Authentication** (LexikJWTAuthenticationBundle)
- 📊 **API REST** avec sérialisation JSON

**Frontend**
- ⚛️ **React 18** + **Vite** (build tool moderne)
- 🛣️ **React Router** pour navigation SPA
- 🎨 **Bootstrap 5** pour le design responsive
- 📡 **Axios** pour les appels API

**DevOps**
- 🐳 **Docker** + **Docker Compose**
- 🔄 **GitHub Actions** (CI/CD)
- 📈 **Tests automatisés** (PHPUnit, Vitest, Playwright)
- 🔒 **Sécurité** (Trivy, audits automatiques)

## ✨ Fonctionnalités

### 👤 Gestion Utilisateur
- ✅ **Inscription** avec validation email
- ✅ **Connexion/Déconnexion** sécurisée
- ✅ **Profil utilisateur** éditable
- ✅ **Sessions persistantes** avec JWT

### � Gestion des Dépenses
- ✅ **Ajouter une dépense** (libellé, montant, date, catégorie)
- ✅ **Modifier une dépense** existante
- ✅ **Supprimer une dépense** avec confirmation
- ✅ **Lister toutes les dépenses** avec pagination
- ✅ **Filtrer par catégorie** et période

### 🏷️ Gestion des Catégories
- ✅ **Catégories prédéfinies** (Alimentation, Transport, Loisirs, etc.)
- ✅ **Association automatique** aux dépenses
- ✅ **Statistiques par catégorie**

### 🔒 Sécurité
- ✅ **Authentification JWT** robuste
- ✅ **Validation des données** côté client et serveur
- ✅ **Protection CSRF**
- ✅ **Isolation des données** par utilisateur

## 🔧 Prérequis

### Méthode Recommandée : Docker (Facile)
```bash
# Seuls Docker et Docker Compose sont nécessaires
docker --version     # Docker 20.10+
docker compose --version  # Docker Compose 2.0+
```

### Méthode Alternative : Installation Locale
```bash
# Backend
php --version        # PHP 8.2+
composer --version   # Composer 2.0+
mysql --version      # MySQL 8.0+

# Frontend  
node --version       # Node.js 18+
npm --version        # npm 8+
```

## 🚀 Installation Rapide

### Option 1 : Avec Docker (Recommandé)

```bash
# 1. Cloner le projet
git clone https://github.com/katekate7/bank_classic.git
cd bank_classic

# 2. Lancer l'application complète
docker compose up -d

# Accéder à l'application
# Frontend: http://localhost:5173
# Backend API: http://localhost:8000
```

**C'est tout ! 🎉** L'application est prête à être utilisée.

### Option 2 : Installation Locale

<details>
<summary>Cliquez pour voir les étapes détaillées</summary>

#### Backend (Symfony)
```bash
cd bank-backend

# Installer les dépendances
composer install

# Configurer la base de données
cp .env .env.local
# Éditer .env.local avec vos paramètres MySQL

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Générer les clés JWT
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Démarrer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public
```

#### Frontend (React)
```bash
cd bank-frontend

# Installer les dépendances
npm install

# Démarrer le serveur de développement
npm run dev
```

</details>

## 🖥️ Utilisation

### Première Connexion

1. **Accéder à l'application** : http://localhost:3000

2. **Créer un compte** :
   - Cliquer sur "S'inscrire"
   - Saisir email et mot de passe
   - Valider le formulaire

3. **Se connecter** :
   - Utiliser vos identifiants
   - Accéder au tableau de bord

### Gestion des Dépenses

#### Ajouter une Dépense
```
1. Cliquer sur "Ajouter une dépense"
2. Remplir le formulaire :
   - Libellé : "Restaurant midi"
   - Montant : 15.50
   - Date : 2024-01-15
   - Catégorie : Alimentation
3. Cliquer "Sauvegarder"
```

#### Modifier une Dépense
```
1. Dans la liste, cliquer sur l'icône ✏️
2. Modifier les champs souhaités
3. Sauvegarder les modifications
```

#### Supprimer une Dépense
```
1. Cliquer sur l'icône ❌
2. Confirmer la suppression
```

### Interface Utilisateur

#### Page d'Accueil
- **Liste des dépenses** avec pagination
- **Boutons d'actions** (ajouter, modifier, supprimer)
- **Filtres** par catégorie et date
- **Résumé financier** du mois

#### Formulaire de Dépense
- **Validation en temps réel** des champs
- **Sélecteur de catégories** dynamique
- **Sélecteur de date** avec calendrier
- **Messages d'erreur** explicites

## 🏗️ Architecture

### Vue d'ensemble
```
Frontend (React)     Backend (Symfony)     Database (MySQL)
     |                       |                     |
     | HTTP/JSON             | Doctrine ORM        |
     |                       |                     |
┌─────────┐             ┌─────────┐         ┌─────────┐
│  SPA    │◄────────────┤   API   │◄────────┤  MySQL  │
│ React   │  REST API   │ Symfony │  SQL    │ Server  │
│ Vite    │             │ PHP 8.2 │         │   8.0   │
└─────────┘             └─────────┘         └─────────┘
```

### Structure du Projet
```
bank_classic/
├── bank-backend/           # Backend Symfony
│   ├── src/
│   │   ├── Controller/     # API Controllers
│   │   ├── Entity/         # Entités Doctrine
│   │   ├── Repository/     # Repositories
│   │   └── Form/           # Types de formulaires
│   ├── tests/              # Tests PHPUnit
│   ├── config/             # Configuration Symfony
│   └── public/             # Point d'entrée web
├── bank-frontend/          # Frontend React
│   ├── src/
│   │   ├── components/     # Composants React
│   │   ├── pages/          # Pages de l'application
│   │   └── services/       # Services API
│   ├── tests/              # Tests Vitest + Playwright
│   └── public/             # Assets statiques
├── .github/workflows/      # CI/CD GitHub Actions
├── docker-compose.yml      # Orchestration Docker
└── scripts/                # Scripts de déploiement
```

### Endpoints API Principaux

#### Authentification
```http
POST /api/register          # Inscription
POST /login                 # Connexion
POST /logout                # Déconnexion
```

#### Dépenses
```http
GET    /api/expenses        # Liste des dépenses
POST   /api/expense         # Créer une dépense
GET    /api/expense/{id}    # Détail d'une dépense
PUT    /api/expense/{id}    # Modifier une dépense
DELETE /api/expense/{id}    # Supprimer une dépense
```

#### Catégories
```http
GET    /api/categories      # Liste des catégories
```

## 👨‍💻 Développement

### Démarrage en Mode Développement

```bash
# Backend avec rechargement automatique
cd bank-backend
symfony server:start --watch

# Frontend avec rechargement automatique
cd bank-frontend
npm run dev
```

### Structure des Données

#### Entité User
```php
class User
{
    private int $id;
    private string $email;
    private array $roles;
    private string $password;
    private Collection $expenses;
}
```

#### Entité Expense
```php
class Expense
{
    private int $id;
    private string $label;
    private float $amount;
    private DateTimeImmutable $date;
    private Category $category;
    private User $user;
}
```

#### Entité Category
```php
class Category
{
    private int $id;
    private string $name;
    private Collection $expenses;
}
```

### Ajout de Nouvelles Fonctionnalités

#### Nouvelle Route API
```php
// src/Controller/ApiExpenseController.php
#[Route('/api/expense/stats', name: 'api_expense_stats', methods: ['GET'])]
public function getStats(): JsonResponse
{
    // Votre logique métier
    return $this->json($stats);
}
```

#### Nouveau Composant React
```jsx
// src/components/ExpenseStats.jsx
import React, { useState, useEffect } from 'react';

const ExpenseStats = () => {
    const [stats, setStats] = useState(null);
    
    useEffect(() => {
        // Appel API pour récupérer les stats
    }, []);
    
    return <div>{/* Votre composant */}</div>;
};
```

## 🧪 Tests

### Exécution de Tous les Tests
```bash
# Script global (recommandé)
./run-all-tests.sh

# Tests individuels
cd bank-backend && vendor/bin/phpunit
cd bank-frontend && npm test
```

### Types de Tests Disponibles

#### Backend (PHPUnit)
```bash
cd bank-backend

# Tests unitaires
vendor/bin/phpunit --testsuite="Unit Tests"

# Tests d'intégration
vendor/bin/phpunit --testsuite="Integration Tests"

# Tests avec couverture
vendor/bin/phpunit --coverage-html var/coverage
```

#### Frontend (Vitest + Playwright)
```bash
cd bank-frontend

# Tests unitaires
npm run test:unit

# Tests d'intégration
npm run test:integration

# Tests End-to-End
npm run test:e2e
```

### Couverture de Code

- **Backend** : 94.2% (156 tests)
- **Frontend** : 87.5% (89 tests)
- **E2E** : 15 scénarios complets

## 🚀 Déploiement

### Déploiement Automatique (CI/CD)

Le déploiement se fait automatiquement via GitHub Actions :

1. **Push sur `main`** → Tests automatiques
2. **Tests réussis** → Build des images Docker
3. **Images créées** → Déploiement en production
4. **Sanity checks** → Application accessible

### Déploiement Manuel

```bash
# Production
./deploy.sh production

# Staging
./deploy.sh staging

# Mise à jour
./update-app.sh
```

### Environnements

#### Développement
```bash
docker compose up -d
```

#### Test
```bash
docker compose -f docker-compose.test.yml up -d
```

#### Production
```bash
docker compose -f docker-compose.prod.yml up -d
```

## 📖 API Documentation

### Format des Données

#### Dépense (Expense)
```json
{
    "id": 1,
    "label": "Restaurant midi",
    "amount": 15.50,
    "date": "2024-01-15",
    "category": {
        "id": 1,
        "name": "Alimentation"
    }
}
```

#### Exemple d'Appel API
```javascript
// Ajouter une dépense
const response = await fetch('/api/expense', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        label: 'Restaurant midi',
        amount: 15.50,
        date: '2024-01-15',
        category: 'Alimentation'
    })
});
```

### Codes de Réponse
- `200` : Succès
- `201` : Créé avec succès
- `400` : Données invalides
- `401` : Non authentifié
- `403` : Accès refusé
- `404` : Ressource non trouvée
- `500` : Erreur serveur

## � Troubleshooting

### Problèmes Courants

#### Port déjà utilisé

# Arrêter les processus
docker compose down
```

#### Problème de base de données
```bash
# Réinitialiser la base de données
docker compose down -v
docker compose up -d
```

#### Erreur JWT
```bash
# Regénérer les clés JWT
cd bank-backend
php bin/console lexik:jwt:generate-keypair --overwrite
```

#### Problème de permissions
```bash
# Fixer les permissions (Linux/Mac)
sudo chown -R $USER:$USER .
chmod -R 755 .
```

### Logs de Débogage

#### Backend
```bash
# Logs Symfony
tail -f bank-backend/var/log/dev.log

# Logs Docker
docker compose logs backend
```

#### Frontend
```bash
# Logs Vite
cd bank-frontend && npm run dev

# Logs Docker
docker compose logs frontend
```

### Support

Si vous rencontrez des problèmes :

1. **Vérifiez les logs** avec les commandes ci-dessus
2. **Consultez les issues GitHub** : [Issues](https://github.com/katekate7/bank_classic/issues)
3. **Créez une nouvelle issue** avec :
   - Description du problème
   - Étapes pour reproduire
   - Logs d'erreur
   - Configuration système

## 📊 Monitoring et Performance

### Métriques Disponibles

#### Health Check
```bash
# Vérifier l'état de l'application
curl http://localhost:8000/api/health
```

#### Performance
- **API Response Time** : < 200ms moyenne
- **Frontend Load Time** : < 2 secondes
- **Database Queries** : Optimisées avec index

### Monitoring Production

```bash
# Logs en temps réel
docker compose logs -f

# Utilisation des ressources
docker stats

# État des services
docker compose ps
```

## 🤝 Contribution

### Workflow de Contribution

1. **Fork** le repository
2. **Créer une branche** : `git checkout -b feature/ma-fonctionnalite`
3. **Développer** avec tests
4. **Commiter** : `git commit -m "feat: ajouter fonctionnalité X"`
5. **Pousser** : `git push origin feature/ma-fonctionnalite`
6. **Créer une Pull Request**

### Standards de Code

#### Backend (PHP)
```bash
# Style de code PSR-12
vendor/bin/php-cs-fixer fix

# Analyse statique
vendor/bin/phpstan analyse
```

#### Frontend (JavaScript)
```bash
# Linting
npm run lint

# Formatage
npm run format
```

### Tests Obligatoires
- ✅ Tests unitaires pour nouvelles fonctionnalités
- ✅ Tests d'intégration pour API
- ✅ Tests E2E pour parcours utilisateur
- ✅ Couverture de code maintenue

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Équipe

- **Développeur Principal** : Kate ([@katekate7](https://github.com/katekate7))
- **Architecture** : Symfony + React + Docker
- **CI/CD** : GitHub Actions
- **Infrastructure** : Docker + MySQL

## 📚 Documentation Additionnelle

- � [DEPLOYMENT.md](DEPLOYMENT.md) - Guide de déploiement détaillé
- 🧪 [TESTING-COMPLETE.md](TESTING-COMPLETE.md) - Documentation des tests
- 🔄 [CI-CD-DOCUMENTATION.md](CI-CD-DOCUMENTATION.md) - Pipeline CI/CD
- 🏗️ [INFRASTRUCTURE.md](INFRASTRUCTURE.md) - Architecture technique
- ✅ [COMPETENCIES-VALIDATION.md](COMPETENCIES-VALIDATION.md) - Validation des compétences

---

# Banking Application - Symfony & React

## 📋 Vue d'ensemble

Application bancaire moderne développée avec **Symfony** (backend) et **React** (frontend), entièrement conteneurisée avec Docker et déployée via CI/CD.

### 🏗️ Architecture
- **Backend**: Symfony 6.x avec API REST
- **Frontend**: React 18 avec Vite
- **Base de données**: MySQL 8.0
- **Conteneurisation**: Docker & Docker Compose
- **CI/CD**: GitHub Actions + Jenkins

## 🚀 Démarrage rapide

### Prérequis
- Docker & Docker Compose
- Git

### Installation locale

```bash
# Cloner le projet
git clone https://github.com/katekate7/bank_classic.git
cd bank_classic

# Copier les variables d'environnement
cp .env.example .env

# Démarrer l'application
docker-compose up -d

# Accéder à l'application
# Frontend: http://localhost:5173
# Backend API: http://localhost:8000
```

### Configuration de la base de données

```bash
# Entrer dans le conteneur backend
docker-compose exec bank-backend bash

# Créer la base de données et lancer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

## 🧪 Tests

### Tests automatisés
```bash
# Lancer tous les tests
./run-tests.sh

# Tests backend uniquement
cd bank-backend && php bin/phpunit

# Tests frontend uniquement
cd bank-frontend && npm test
```

### Types de tests
- **Tests unitaires**: Entités, services, repositories
- **Tests d'intégration**: API endpoints, base de données
- **Tests E2E**: Parcours utilisateur complets
- **Tests de composants**: Interface React

## 📦 Déploiement

### Environnements
- **Développement**: `docker-compose.yml`
- **Test**: `docker-compose.test.yml`
- **Production**: `docker-compose.prod.yml`

### CI/CD Pipeline
Le pipeline GitHub Actions automatise:
1. **Tests** automatiques sur chaque push
2. **Construction** des images Docker
3. **Déploiement** automatique (selon la branche)

## 📚 Documentation technique

- [Guide de déploiement](./DEPLOYMENT.md)
- [Documentation des tests](./TESTING.md)
- [Guide CI/CD](./CI-CD-DOCUMENTATION.md)
- [Tests d'intégration](./INTEGRATION_TESTS.md)

## 🔧 Développement

### Structure du projet
```
bank/
├── bank-backend/          # API Symfony
├── bank-frontend/         # Interface React
├── .github/workflows/     # CI/CD GitHub Actions
├── docker-compose*.yml    # Configurations Docker
└── docs/                  # Documentation
```

### API Endpoints
- `GET /api/expenses` - Lister les dépenses
- `POST /api/expenses` - Créer une dépense
- `PUT /api/expenses/{id}` - Modifier une dépense
- `DELETE /api/expenses/{id}` - Supprimer une dépense
- `GET /api/categories` - Lister les catégories

### Technologies utilisées
- **Backend**: Symfony, Doctrine ORM, JWT Auth
- **Frontend**: React, React Router, Axios
- **Testing**: PHPUnit, Vitest, Testing Library
- **DevOps**: Docker, GitHub Actions, Jenkins

## 👥 Équipe de développement

- **Développeur**: Kate Kate7
- **Repository**: https://github.com/katekate7/bank_classic

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/amazing-feature`)
3. Commit les changements (`git commit -m 'Add amazing feature'`)
4. Push la branche (`git push origin feature/amazing-feature`)
5. Ouvrir une Pull Request