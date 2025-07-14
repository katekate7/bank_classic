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
