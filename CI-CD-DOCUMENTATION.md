# Documentation CI/CD - Banking Application

## 🔄 Processus d'Intégration Continue (CI)

### Vue d'ensemble
Le processus CI/CD automatise les tests, la construction et le déploiement de l'application bancaire à travers GitHub Actions et Jenkins.

### 📋 Pipeline GitHub Actions

Le fichier `.github/workflows/ci-cd.yml` définit notre pipeline automatisé :

#### Déclencheurs
- **Push** sur `main` et `develop`
- **Pull Request** vers `main`
- **Manual dispatch** pour déploiements d'urgence

#### Étapes du pipeline

##### 1. **Tests Backend (Symfony)**
```yaml
- Checkout du code
- Configuration PHP 8.2
- Installation des dépendances (Composer)
- Configuration MySQL pour tests
- Création de la base de données de test
- Exécution des migrations
- Lancement des tests PHPUnit
- Génération des rapports de couverture
```

##### 2. **Tests Frontend (React)**
```yaml
- Checkout du code
- Configuration Node.js 18
- Installation des dépendances (npm)
- Linting du code (ESLint)
- Tests unitaires (Vitest)
- Tests de composants (Testing Library)
- Build de production
```

##### 3. **Construction Docker**
```yaml
- Construction des images Docker
- Tests de sécurité des images
- Push vers Docker Hub (si tests passent)
```

##### 4. **Déploiement automatique**
```yaml
- Déploiement sur environnement de staging
- Tests d'acceptation automatisés
- Déploiement en production (si branche main)
```

## 🛠️ Outils de Qualité de Code

### Backend (Symfony)
- **PHPUnit**: Tests unitaires et d'intégration
- **PHPStan**: Analyse statique du code PHP
- **PHP CS Fixer**: Formatage automatique du code
- **Symfony Security Checker**: Vérification des vulnérabilités

### Frontend (React)
- **ESLint**: Linting JavaScript/TypeScript
- **Prettier**: Formatage du code
- **Vitest**: Framework de tests
- **Testing Library**: Tests d'interface utilisateur

### DevOps
- **Docker Scout**: Analyse de sécurité des images
- **Hadolint**: Linting des Dockerfiles
- **Trivy**: Scanner de vulnérabilités

## 📊 Rapports et Métriques

### Couverture de code
- **Backend**: Minimum 80% de couverture
- **Frontend**: Minimum 75% de couverture
- Rapports générés automatiquement et stockés comme artifacts

### Métriques de qualité
```bash
# Génération des métriques backend
php bin/phpunit --coverage-html coverage/
vendor/bin/phpstan analyse src/

# Métriques frontend  
npm run test:coverage
npm run lint:report
```

## 🔧 Configuration des Environnements

### Variables d'environnement par environnement

#### Développement
```env
APP_ENV=dev
DATABASE_URL=mysql://root:@localhost:3306/bank_dev
```

#### Test
```env
APP_ENV=test
DATABASE_URL=mysql://root:@localhost:3306/bank_test
```

#### Production
```env
APP_ENV=prod
DATABASE_URL=mysql://user:password@db:3306/bank_prod
```

## 🚀 Déploiement Continu (CD)

### Stratégie de déploiement
- **Blue-Green Deployment**: Déploiement sans interruption
- **Rollback automatique**: En cas d'échec des tests post-déploiement
- **Monitoring continu**: Surveillance des performances

### Processus de mise à jour automatique

#### 1. **Validation du code**
```bash
# Lorsqu'un développeur pousse du code
git push origin main

# GitHub Actions se déclenche automatiquement
# - Exécute tous les tests
# - Vérifie la qualité du code
# - Construit les images Docker
```

#### 2. **Tests d'intégration**
```bash
# Déploiement automatique en staging
docker-compose -f docker-compose.test.yml up -d

# Exécution des tests E2E
npm run test:e2e
php bin/phpunit --group=integration
```

#### 3. **Déploiement en production**
```bash
# Si tous les tests passent
docker-compose -f docker-compose.prod.yml up -d

# Vérification de santé
curl -f http://production-url/health || exit 1
```

## 📈 Monitoring et Alertes

### Health Checks automatiques
```yaml
# Vérification backend
GET /api/health
Response: {"status": "ok", "database": "connected"}

# Vérification frontend
GET /health
Response: {"status": "ok", "version": "1.0.0"}
```

### Notifications automatiques
- **Slack**: Notifications en temps réel
- **Email**: Rapports quotidiens
- **Teams**: Alertes critiques

## 🔒 Sécurité et Secrets

### Gestion des secrets
- Utilisation de GitHub Secrets
- Rotation automatique des clés
- Chiffrement des variables sensibles

### Secrets requis
```
DOCKER_USERNAME: Identifiant Docker Hub
DOCKER_PASSWORD: Token Docker Hub  
DATABASE_PASSWORD: Mot de passe base de données
JWT_SECRET: Clé secrète JWT
APP_SECRET: Clé secrète Symfony
```

## 📝 Logs et Debugging

### Accès aux logs
```bash
# Logs du pipeline CI/CD
# Disponibles dans l'onglet Actions de GitHub

# Logs applicatifs
docker-compose logs bank-backend
docker-compose logs bank-frontend

# Logs de déploiement
kubectl logs -f deployment/bank-app
```

### Debugging des échecs de pipeline
1. Vérifier les logs dans GitHub Actions
2. Reproduire localement avec les mêmes conditions
3. Utiliser les artifacts téléchargés
4. Tester en environnement de staging

## 🎯 Bonnes Pratiques

### Commits et branches
- **Commits atomiques**: Un changement = un commit
- **Messages descriptifs**: Convention Conventional Commits
- **Branches feature**: Une fonctionnalité = une branche
- **Code reviews obligatoires**: Avant merge en main

### Tests et qualité
- **Tests avant commit**: Pre-commit hooks
- **Couverture maintenue**: Pas de régression
- **Documentation à jour**: README et docs techniques
- **Sécurité first**: Scan automatique des vulnérabilités

## 🆘 Résolution de Problèmes

### Échecs de tests courants
```bash
# Tests de base de données échouent
# Vérifier la configuration MySQL en CI

# Tests frontend échouent  
# Vérifier les dépendances Node.js

# Images Docker ne se construisent pas
# Vérifier les Dockerfiles et dépendances
```

### Contacts Support
- **DevOps Lead**: @katekate7
- **Repository**: https://github.com/katekate7/bank_classic
- **Documentation**: [Lien vers wiki interne]
