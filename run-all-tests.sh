#!/bin/bash

# Script d'exécution de tous les tests - Bank Application
# Ce script exécute tous les types de tests : unitaires, intégration, système, acceptation et E2E

set -e  # Arrêter le script si une commande échoue

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction d'affichage avec couleur
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Variables
BACKEND_DIR="bank-backend"
FRONTEND_DIR="bank-frontend"
START_TIME=$(date +%s)

print_status "🚀 Démarrage de la suite complète de tests pour l'application Bank"
print_status "Timestamp: $(date)"

# Vérifier que Docker est installé et en cours d'exécution
if ! command -v docker &> /dev/null; then
    print_error "Docker n'est pas installé ou n'est pas dans le PATH"
    exit 1
fi

if ! docker info &> /dev/null; then
    print_error "Docker n'est pas en cours d'exécution"
    exit 1
fi

# Fonction de nettoyage en cas d'interruption
cleanup() {
    print_warning "Nettoyage en cours..."
    docker compose -f docker-compose.test.yml down --volumes --remove-orphans 2>/dev/null || true
    exit 1
}

trap cleanup SIGINT SIGTERM

# 1. Tests Backend (Symfony)
print_status "📦 Démarrage des tests backend (Symfony)..."

cd $BACKEND_DIR

# Vérifier que Composer est installé
if ! command -v composer &> /dev/null; then
    print_error "Composer n'est pas installé"
    exit 1
fi

# Installation des dépendances
print_status "Installation des dépendances Composer..."
composer install --no-interaction --optimize-autoloader

# Configuration de la base de données de test
print_status "Configuration de la base de données de test..."
php bin/console doctrine:database:drop --if-exists --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --no-interaction --env=test

# Tests unitaires backend
print_status "🧪 Exécution des tests unitaires backend..."
if vendor/bin/phpunit --testsuite="Unit Tests" --coverage-text; then
    print_success "Tests unitaires backend réussis"
else
    print_error "Échec des tests unitaires backend"
    exit 1
fi

# Tests d'intégration backend
print_status "🔄 Exécution des tests d'intégration backend..."
if vendor/bin/phpunit --testsuite="Integration Tests" --configuration phpunit.integration.xml; then
    print_success "Tests d'intégration backend réussis"
else
    print_error "Échec des tests d'intégration backend"
    exit 1
fi

# Tests système
print_status "🏗️ Exécution des tests système..."
if vendor/bin/phpunit --testsuite="System Tests" --configuration phpunit.integration.xml; then
    print_success "Tests système réussis"
else
    print_error "Échec des tests système"
    exit 1
fi

# Tests d'acceptation
print_status "✅ Exécution des tests d'acceptation..."
if vendor/bin/phpunit --testsuite="Acceptance Tests" --configuration phpunit.integration.xml; then
    print_success "Tests d'acceptation réussis"
else
    print_error "Échec des tests d'acceptation"
    exit 1
fi

cd ..

# 2. Tests Frontend (React)
print_status "⚛️ Démarrage des tests frontend (React)..."

cd $FRONTEND_DIR

# Vérifier que Node.js et npm sont installés
if ! command -v node &> /dev/null; then
    print_error "Node.js n'est pas installé"
    exit 1
fi

if ! command -v npm &> /dev/null; then
    print_error "npm n'est pas installé"
    exit 1
fi

# Installation des dépendances
print_status "Installation des dépendances npm..."
npm ci

# Tests unitaires frontend
print_status "🧪 Exécution des tests unitaires frontend..."
if npm run test:unit; then
    print_success "Tests unitaires frontend réussis"
else
    print_error "Échec des tests unitaires frontend"
    exit 1
fi

# Tests d'intégration frontend
print_status "🔄 Exécution des tests d'intégration frontend..."
if npm run test:integration; then
    print_success "Tests d'intégration frontend réussis"
else
    print_error "Échec des tests d'intégration frontend"
    exit 1
fi

cd ..

# 3. Tests End-to-End avec Docker
print_status "🌐 Démarrage des tests End-to-End..."

# Démarrer l'application complète avec Docker
print_status "🐳 Démarrage de l'application avec Docker Compose..."
docker compose -f docker-compose.test.yml up -d

# Attendre que les services soient prêts
print_status "⏳ Attente que les services soient prêts..."
sleep 30

# Vérifier que les services sont accessibles
print_status "🔍 Vérification de la disponibilité des services..."

# Vérifier le backend
for i in {1..30}; do
    if curl -f http://localhost:8000/api/health &> /dev/null; then
        print_success "Backend accessible"
        break
    fi
    if [ $i -eq 30 ]; then
        print_error "Le backend n'est pas accessible après 30 tentatives"
        docker compose -f docker-compose.test.yml logs backend
        exit 1
    fi
    sleep 1
done

# Vérifier le frontend
for i in {1..30}; do
    if curl -f http://localhost:3000 &> /dev/null; then
        print_success "Frontend accessible"
        break
    fi
    if [ $i -eq 30 ]; then
        print_error "Le frontend n'est pas accessible après 30 tentatives"
        docker compose -f docker-compose.test.yml logs frontend
        exit 1
    fi
    sleep 1
done

# Exécuter les tests E2E
cd $FRONTEND_DIR
print_status "🎭 Installation de Playwright..."
npx playwright install --with-deps

print_status "🌐 Exécution des tests End-to-End..."
if npx playwright test; then
    print_success "Tests End-to-End réussis"
else
    print_error "Échec des tests End-to-End"
    cd ..
    docker compose -f docker-compose.test.yml logs
    docker compose -f docker-compose.test.yml down --volumes
    exit 1
fi

cd ..

# 4. Tests de performance (optionnel)
print_status "⚡ Exécution des tests de performance..."

if command -v artillery &> /dev/null; then
    print_status "🎯 Tests de charge avec Artillery..."
    if artillery run tests/performance/load-test.yml; then
        print_success "Tests de performance réussis"
    else
        print_warning "Tests de performance échoués (non bloquant)"
    fi
else
    print_warning "Artillery n'est pas installé, tests de performance ignorés"
fi

# 5. Tests de sécurité (optionnel)
print_status "🔒 Exécution des tests de sécurité..."

# Audit npm
cd $FRONTEND_DIR
if npm audit --audit-level high; then
    print_success "Audit de sécurité npm réussi"
else
    print_warning "Vulnérabilités détectées dans les dépendances npm"
fi

cd ..

# Nettoyage
print_status "🧹 Nettoyage des ressources..."
docker compose -f docker-compose.test.yml down --volumes --remove-orphans

# Calcul du temps d'exécution
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
MINUTES=$((DURATION / 60))
SECONDS=$((DURATION % 60))

print_success "🎉 Tous les tests ont été exécutés avec succès !"
print_status "⏱️ Temps total d'exécution: ${MINUTES}m ${SECONDS}s"

# Génération du rapport de synthèse
print_status "📊 Génération du rapport de synthèse..."

cat << EOF > test-report.md
# Rapport d'Exécution des Tests - Application Bank

**Date d'exécution:** $(date)
**Durée totale:** ${MINUTES}m ${SECONDS}s
**Statut:** ✅ SUCCÈS

## Tests Exécutés

### Backend (Symfony)
- ✅ Tests unitaires
- ✅ Tests d'intégration  
- ✅ Tests système
- ✅ Tests d'acceptation

### Frontend (React)
- ✅ Tests unitaires
- ✅ Tests d'intégration

### End-to-End
- ✅ Tests E2E avec Playwright

### Performance
- ⚠️ Tests de charge (optionnel)

### Sécurité
- ⚠️ Audit de sécurité

## Couverture de Code

La couverture de code détaillée est disponible dans :
- Backend: \`bank-backend/var/coverage/html/index.html\`
- Frontend: \`bank-frontend/coverage/index.html\`

## Artefacts de Test

- Rapports PHPUnit: \`bank-backend/var/test-results/\`
- Rapports Playwright: \`bank-frontend/playwright-report/\`

EOF

print_success "📄 Rapport généré: test-report.md"
print_status "🏁 Exécution terminée avec succès !"
