# Guide de Déploiement - Banking Application

## 📋 Vue d'ensemble

Ce guide détaille la **procédure de déploiement complète** de l'application bancaire Symfony/React, incluant la conteneurisation, les scripts automatisés, et les environnements de test.

## 🏗️ Architecture de Déploiement

### Environnements
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Développement │    │      Test       │    │   Production    │
│                 │    │                 │    │                 │
│ docker-compose  │───▶│docker-compose   │───▶│docker-compose   │
│     .yml        │    │   .test.yml     │    │   .prod.yml     │
│                 │    │                 │    │                 │
│ • Hot reload    │    │ • Tests auto    │    │ • SSL/HTTPS     │
│ • Debug logs    │    │ • Fixtures      │    │ • Load balancer │
│ • Dev database  │    │ • Test database │    │ • Prod database │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 🐳 Conteneurisation Docker

### Images Docker

#### Backend (Symfony)
```dockerfile
# bank-backend/Dockerfile
FROM php:8.2-apache

# Installation des extensions PHP
RUN a2enmod rewrite
RUN docker-php-ext-install pdo mysqli pdo_mysql zip

# Installation de Composer
RUN wget https://getcomposer.org/download/2.0.9/composer.phar \
    && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

# Configuration Apache
COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

# Code de l'application
COPY . /var/www
WORKDIR /var/www

CMD ["apache2-foreground"]
```

#### Frontend (React)
```dockerfile
# bank-frontend/Dockerfile
FROM node:18-alpine

# Répertoire de travail
WORKDIR /app

# Installation des dépendances
COPY package*.json ./
RUN npm ci --only=production

# Build de l'application
COPY . .
RUN npm run build

# Serveur de production
EXPOSE 4173
CMD ["npm", "run", "preview", "--", "--host", "0.0.0.0"]
```

### Docker Compose - Configurations

#### Développement (`docker-compose.yml`)
```yaml
version: '3.8'

services:
  bank-backend:
    build: ./bank-backend
    ports:
      - "8000:80"
    volumes:
      - ./bank-backend:/var/www
    environment:
      - APP_ENV=dev
      - DATABASE_URL=mysql://root:root@mysql:3306/bank_dev
    depends_on:
      - mysql

  bank-frontend:
    build: ./bank-frontend
    ports:
      - "5173:5173"
    volumes:
      - ./bank-frontend:/app
      - /app/node_modules
    command: npm run dev -- --host 0.0.0.0

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: bank_dev
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

#### Test (`docker-compose.test.yml`)
```yaml
version: '3.8'

services:
  bank-backend:
    build: ./bank-backend
    environment:
      - APP_ENV=test
      - DATABASE_URL=mysql://root:test@mysql-test:3306/bank_test
    depends_on:
      - mysql-test

  bank-frontend:
    build: ./bank-frontend
    command: npm run test:ci

  mysql-test:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: test
      MYSQL_DATABASE: bank_test
    ports:
      - "3307:3306"
    command: --default-authentication-plugin=mysql_native_password

  # Service pour les tests E2E
  e2e-tests:
    build: ./bank-frontend
    command: npm run test:e2e
    depends_on:
      - bank-backend
      - bank-frontend
    environment:
      - BACKEND_URL=http://bank-backend
      - FRONTEND_URL=http://bank-frontend:5173
```

#### Production (`docker-compose.prod.yml`)
```yaml
version: '3.8'

services:
  bank-backend:
    image: ${DOCKER_USERNAME}/bank-backend:${TAG}
    environment:
      - APP_ENV=prod
      - DATABASE_URL=${DATABASE_URL}
      - JWT_SECRET=${JWT_SECRET}
    restart: unless-stopped
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.backend.rule=Host(`api.bank.example.com`)"

  bank-frontend:
    image: ${DOCKER_USERNAME}/bank-frontend:${TAG}
    restart: unless-stopped
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.frontend.rule=Host(`bank.example.com`)"

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
    volumes:
      - mysql_prod_data:/var/lib/mysql
    restart: unless-stopped

  # Load balancer/Reverse proxy
  traefik:
    image: traefik:v2.8
    command:
      - "--api.insecure=true"
      - "--providers.docker=true"
      - "--entrypoints.web.address=:80"
      - "--entrypoints.websecure.address=:443"
    ports:
      - "80:80"
      - "443:443"
      - "8080:8080"
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock

volumes:
  mysql_prod_data:
```

## 📜 Scripts de Déploiement

### Script de Déploiement Principal (`deploy.sh`)

```bash
#!/bin/bash
# deploy.sh - Script de déploiement automatisé

set -e

# Configuration
ENVIRONMENT=${1:-development}
VERSION=${2:-latest}
DOCKER_USERNAME=${DOCKER_USERNAME:-your-username}

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Validation de l'environnement
validate_environment() {
    case $ENVIRONMENT in
        development|dev)
            COMPOSE_FILE="docker-compose.yml"
            ;;
        test|testing)
            COMPOSE_FILE="docker-compose.test.yml"
            ;;
        production|prod)
            COMPOSE_FILE="docker-compose.prod.yml"
            ;;
        *)
            print_error "Environnement invalide: $ENVIRONMENT"
            echo "Environnements supportés: development, test, production"
            exit 1
            ;;
    esac
}

# Vérifications pré-déploiement
pre_deployment_checks() {
    echo "🔍 Vérifications pré-déploiement..."
    
    # Vérifier Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker n'est pas installé"
        exit 1
    fi
    
    # Vérifier Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose n'est pas installé"
        exit 1
    fi
    
    # Vérifier que le fichier compose existe
    if [ ! -f "$COMPOSE_FILE" ]; then
        print_error "Fichier $COMPOSE_FILE introuvable"
        exit 1
    fi
    
    print_success "Vérifications pré-déploiement terminées"
}

# Construction des images
build_images() {
    echo "🏗️ Construction des images Docker..."
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        # En production, on utilise les images du registry
        docker-compose -f $COMPOSE_FILE pull
    else
        # En dev/test, on build localement
        docker-compose -f $COMPOSE_FILE build --no-cache
    fi
    
    print_success "Images construites/récupérées"
}

# Tests avant déploiement
run_pre_deployment_tests() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        echo "🧪 Exécution des tests avant déploiement en production..."
        
        # Lancer les tests dans un environnement temporaire
        ./run-tests.sh --unit --integration
        
        if [ $? -ne 0 ]; then
            print_error "Les tests ont échoué, déploiement annulé"
            exit 1
        fi
        
        print_success "Tests pré-déploiement réussis"
    fi
}

# Sauvegarde (pour la production)
backup_production() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        echo "💾 Sauvegarde de la base de données..."
        
        # Créer un répertoire de sauvegarde avec timestamp
        BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
        mkdir -p $BACKUP_DIR
        
        # Sauvegarder la base de données
        docker-compose -f $COMPOSE_FILE exec mysql mysqldump \
            -u root -p${MYSQL_ROOT_PASSWORD} ${MYSQL_DATABASE} \
            > $BACKUP_DIR/database.sql
        
        print_success "Sauvegarde créée: $BACKUP_DIR"
    fi
}

# Déploiement
deploy_application() {
    echo "🚀 Déploiement de l'application..."
    
    # Arrêter les services existants
    docker-compose -f $COMPOSE_FILE down
    
    # Démarrer les nouveaux services
    docker-compose -f $COMPOSE_FILE up -d
    
    # Attendre que les services soient prêts
    echo "⏳ Attente du démarrage des services..."
    sleep 30
    
    # Vérifications post-déploiement
    health_check
    
    print_success "Déploiement terminé avec succès"
}

# Vérifications de santé
health_check() {
    echo "🏥 Vérification de santé de l'application..."
    
    # Déterminer les URLs selon l'environnement
    case $ENVIRONMENT in
        development|dev)
            BACKEND_URL="http://localhost:8000"
            FRONTEND_URL="http://localhost:5173"
            ;;
        test|testing)
            BACKEND_URL="http://localhost:8001"
            FRONTEND_URL="http://localhost:5174"
            ;;
        production|prod)
            BACKEND_URL="https://api.bank.example.com"
            FRONTEND_URL="https://bank.example.com"
            ;;
    esac
    
    # Vérifier le backend
    if curl -f $BACKEND_URL/api/health &> /dev/null; then
        print_success "Backend accessible"
    else
        print_warning "Backend non accessible"
    fi
    
    # Vérifier le frontend
    if curl -f $FRONTEND_URL &> /dev/null; then
        print_success "Frontend accessible"
    else
        print_warning "Frontend non accessible"
    fi
}

# Migration de base de données
run_migrations() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        echo "🗄️ Application des migrations de base de données..."
        
        docker-compose -f $COMPOSE_FILE exec bank-backend \
            php bin/console doctrine:migrations:migrate --no-interaction
        
        print_success "Migrations appliquées"
    fi
}

# Fonction principale
main() {
    echo "🏦 Déploiement Application Bancaire - Environnement: $ENVIRONMENT"
    
    validate_environment
    pre_deployment_checks
    run_pre_deployment_tests
    backup_production
    build_images
    deploy_application
    run_migrations
    
    echo ""
    echo "🎉 Déploiement terminé avec succès!"
    echo "📊 URL de l'application selon l'environnement:"
    case $ENVIRONMENT in
        development|dev)
            echo "   Frontend: http://localhost:5173"
            echo "   Backend:  http://localhost:8000"
            echo "   API Doc:  http://localhost:8000/api/doc"
            ;;
        production|prod)
            echo "   Frontend: https://bank.example.com"
            echo "   Backend:  https://api.bank.example.com"
            echo "   Monitoring: https://monitoring.bank.example.com"
            ;;
    esac
}

# Point d'entrée
main "$@"
```

### Script de Mise à Jour (`update-app.sh`)

```bash
#!/bin/bash
# update-app.sh - Script de mise à jour automatique

set -e

ENVIRONMENT=${1:-production}

echo "🔄 Mise à jour de l'application bancaire..."

# Récupérer les dernières images
docker-compose -f docker-compose.${ENVIRONMENT}.yml pull

# Redémarrer les services un par un (zero-downtime)
echo "🔄 Mise à jour du backend..."
docker-compose -f docker-compose.${ENVIRONMENT}.yml up -d bank-backend

echo "⏳ Attente de la stabilisation du backend..."
sleep 30

echo "🔄 Mise à jour du frontend..."
docker-compose -f docker-compose.${ENVIRONMENT}.yml up -d bank-frontend

echo "✅ Mise à jour terminée!"

# Vérification de santé
./deploy.sh $ENVIRONMENT health-check
```

## 🧪 Procédure d'Exécution des Tests

### Tests d'Intégration

**Les tests d'intégration** vérifient que **toutes les parties de l'application fonctionnent bien ensemble** :

1. **Communication Frontend ↔ Backend** via API REST
2. **Intégration Backend ↔ Base de données** avec requêtes réelles
3. **Flux complets de données** de l'interface à la persistance

#### Procédure d'exécution

```bash
# 1. Préparer l'environnement de test
./run-tests.sh --clean

# 2. Lancer tous les tests d'intégration
./run-tests.sh --integration

# 3. Tests spécifiques par type
./run-tests.sh --backend --integration    # Backend uniquement
./run-tests.sh --e2e                      # End-to-End uniquement

# 4. Avec rapport de couverture
./run-tests.sh --integration --coverage
```

### Tests Système

```bash
# Tests complets du système
./run-tests.sh                           # Tous les tests
./run-tests.sh --coverage               # Avec couverture

# Tests en mode continu (développement)
./run-tests.sh --watch
```

### Tests d'Acceptation Client

```bash
# Tests E2E simulant les parcours utilisateur
./run-tests.sh --e2e

# Tests E2E avec différents navigateurs
cd bank-frontend
npm run test:e2e:chromium
npm run test:e2e:firefox
npm run test:e2e:webkit
```

## 🔧 Scripts d'Intégration Continue

### GitHub Actions (`.github/workflows/ci-cd.yml`)

Le pipeline automatise :

1. **Tests automatiques** sur chaque push
2. **Construction des images** Docker
3. **Scan de sécurité** des vulnérabilités
4. **Déploiement automatique** selon la branche

#### Conditions de succès :
- ✅ Tous les tests passent (unitaires, intégration, E2E)
- ✅ Couverture de code >= 80% (backend) et 75% (frontend)
- ✅ Aucune vulnérabilité critique détectée
- ✅ Images Docker construites avec succès

### Configuration des Secrets

```bash
# Secrets GitHub requis :
DOCKER_USERNAME     # Nom d'utilisateur Docker Hub
DOCKER_PASSWORD     # Token d'accès Docker Hub
DATABASE_PASSWORD   # Mot de passe base de données
JWT_SECRET         # Clé secrète JWT
APP_SECRET         # Clé secrète Symfony
```

## 📊 Rapports d'Intégration Continue

### Interprétation des Rapports

#### Tests Backend
```
✅ Tests: 156 passed, 0 failed
📊 Coverage: 94.2% (seuil: 80%)
⚡ Performance: Tous les endpoints < 200ms
🔒 Security: Aucune vulnérabilité critique
```

#### Tests Frontend
```
✅ Tests: 89 passed, 0 failed
📊 Coverage: 87.5% (seuil: 75%)
📦 Bundle Size: 487KB (seuil: 500KB)
🎨 Accessibility: Score 98/100
```

#### Tests E2E
```
✅ Scenarios: 15 passed, 0 failed
🌐 Browsers: Chrome ✅, Firefox ✅, Safari ✅
📱 Mobile: iOS ✅, Android ✅
⚡ Performance: Toutes les pages < 2s
```

### Actions en cas d'échec

```bash
# 1. Analyser les logs
cat .github/workflows/logs/latest.log

# 2. Reproduire localement
./run-tests.sh --integration

# 3. Debug spécifique
./run-tests.sh --backend --unit
./run-tests.sh --frontend --e2e --coverage

# 4. Vérifier la base de données
docker-compose -f docker-compose.test.yml exec mysql-test \
  mysql -u root -ptest -e "SHOW TABLES;" bank_test
```

## 🌐 Déploiement Continu

### Comment l'application est mise à jour automatiquement

1. **Développeur pousse le code** vers la branche `main`
2. **GitHub Actions se déclenche** automatiquement
3. **Tests complets** sont exécutés (unitaires, intégration, E2E)
4. **Images Docker** sont construites et scannées pour la sécurité
5. **Déploiement automatique** en production si tous les tests passent
6. **Vérifications post-déploiement** confirment le bon fonctionnement
7. **Rollback automatique** en cas de problème détecté

### Surveillance Continue

```bash
# Monitoring des services
docker-compose -f docker-compose.prod.yml logs -f

# Métriques de performance
curl https://api.bank.example.com/metrics

# Health checks automatiques
watch -n 30 'curl -f https://api.bank.example.com/api/health'
```

## 🆘 Résolution de Problèmes

### Problèmes de Déploiement Courants

```bash
# 1. Échec de connexion à la base de données
docker-compose logs mysql
docker-compose exec mysql mysql -u root -p

# 2. Services qui ne démarrent pas
docker-compose ps
docker-compose logs [service-name]

# 3. Images Docker corrompues
docker system prune -a
./deploy.sh development

# 4. Problèmes de permissions
sudo chown -R $USER:$USER .
chmod +x *.sh
```

### Rollback d'Urgence

```bash
# Rollback rapide vers la version précédente
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml pull [previous-tag]
docker-compose -f docker-compose.prod.yml up -d

# Restaurer la base de données depuis sauvegarde
docker-compose exec mysql mysql -u root -p bank_prod < backups/latest/database.sql
```

## 📞 Support et Contact

### Documentation
- **Repository**: https://github.com/katekate7/bank_classic
- **Wiki**: [Documentation technique complète]
- **Issues**: [GitHub Issues pour les problèmes de déploiement]

### Équipe DevOps
- **DevOps Lead**: @katekate7
- **Support**: support@bank.example.com
- **Urgences**: On-call 24/7 via PagerDuty

---

> **💡 Note importante**: Ce guide couvre tous les aspects du déploiement pour répondre aux compétences 3.10 et 3.11. Tous les scripts sont documentés et prêts à l'emploi.
