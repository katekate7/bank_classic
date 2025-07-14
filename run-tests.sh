#!/bin/bash

# run-tests.sh - Script de lancement des tests pour l'application bancaire
# Usage: ./run-tests.sh [options]
# Options:
#   --unit           Lancer uniquement les tests unitaires
#   --integration    Lancer uniquement les tests d'intégration  
#   --e2e           Lancer uniquement les tests E2E
#   --coverage      Générer les rapports de couverture
#   --watch         Mode watch pour le développement
#   --help          Afficher cette aide

set -e

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKEND_DIR="bank-backend"
FRONTEND_DIR="bank-frontend"
TEST_DB_URL="mysql://root:test@localhost:3307/bank_test"

# Fonctions utilitaires
print_header() {
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# Fonction d'aide
show_help() {
    echo "Script de test pour l'application bancaire Symfony/React"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --unit           Lancer uniquement les tests unitaires"
    echo "  --integration    Lancer uniquement les tests d'intégration"
    echo "  --e2e           Lancer uniquement les tests End-to-End"
    echo "  --coverage      Générer les rapports de couverture détaillés"
    echo "  --watch         Mode watch pour le développement"
    echo "  --backend       Lancer uniquement les tests backend"
    echo "  --frontend      Lancer uniquement les tests frontend"
    echo "  --clean         Nettoyer l'environnement de test avant de commencer"
    echo "  --help          Afficher cette aide"
    echo ""
    echo "Exemples:"
    echo "  $0                    # Lancer tous les tests"
    echo "  $0 --unit            # Tests unitaires uniquement"
    echo "  $0 --backend --coverage  # Tests backend avec couverture"
    echo "  $0 --clean --e2e     # Nettoyer puis lancer les tests E2E"
}

# Vérifier si Docker est installé et en cours d'exécution
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker n'est pas installé"
        exit 1
    fi
    
    if ! docker info &> /dev/null; then
        print_error "Docker n'est pas en cours d'exécution"
        exit 1
    fi
}

# Vérifier si les dépendances sont installées
check_dependencies() {
    print_info "Vérification des dépendances..."
    
    # Backend dependencies
    if [ ! -d "$BACKEND_DIR/vendor" ]; then
        print_warning "Dépendances backend manquantes, installation en cours..."
        cd $BACKEND_DIR
        composer install --prefer-dist --no-progress
        cd ..
    fi
    
    # Frontend dependencies
    if [ ! -d "$FRONTEND_DIR/node_modules" ]; then
        print_warning "Dépendances frontend manquantes, installation en cours..."
        cd $FRONTEND_DIR
        npm ci
        cd ..
    fi
    
    print_success "Dépendances vérifiées"
}

# Nettoyer l'environnement de test
clean_environment() {
    print_header "🧹 NETTOYAGE DE L'ENVIRONNEMENT DE TEST"
    
    # Arrêter les conteneurs de test s'ils sont en cours d'exécution
    if docker-compose -f docker-compose.test.yml ps -q | grep -q .; then
        print_info "Arrêt des conteneurs de test..."
        docker-compose -f docker-compose.test.yml down
    fi
    
    # Nettoyer les caches
    print_info "Nettoyage des caches..."
    if [ -d "$BACKEND_DIR/var/cache/test" ]; then
        rm -rf $BACKEND_DIR/var/cache/test
    fi
    
    if [ -d "$FRONTEND_DIR/.vitest" ]; then
        rm -rf $FRONTEND_DIR/.vitest
    fi
    
    # Nettoyer les rapports précédents
    if [ -d "$BACKEND_DIR/coverage" ]; then
        rm -rf $BACKEND_DIR/coverage
    fi
    
    if [ -d "$FRONTEND_DIR/coverage" ]; then
        rm -rf $FRONTEND_DIR/coverage
    fi
    
    print_success "Environnement nettoyé"
}

# Démarrer les services de test
start_test_services() {
    print_header "🚀 DÉMARRAGE DES SERVICES DE TEST"
    
    print_info "Démarrage de MySQL de test..."
    docker-compose -f docker-compose.test.yml up -d mysql-test
    
    # Attendre que MySQL soit prêt
    print_info "Attente du démarrage de MySQL..."
    timeout 60 bash -c '
    until docker-compose -f docker-compose.test.yml exec mysql-test mysql -u root -ptest -e "SELECT 1" >/dev/null 2>&1; do
        echo "Attente de MySQL..."
        sleep 2
    done'
    
    print_success "MySQL de test démarré"
}

# Configurer la base de données de test
setup_test_database() {
    print_header "🗄️ CONFIGURATION DE LA BASE DE DONNÉES DE TEST"
    
    cd $BACKEND_DIR
    
    # Créer le fichier .env.test
    print_info "Configuration de l'environnement de test..."
    if [ ! -f .env.test ]; then
        cp .env .env.test
    fi
    
    # Mettre à jour l'URL de la base de données de test
    echo "DATABASE_URL=$TEST_DB_URL" > .env.test
    echo "APP_ENV=test" >> .env.test
    
    # Créer et configurer la base de données
    print_info "Création de la base de données de test..."
    php bin/console doctrine:database:create --env=test --if-not-exists
    
    print_info "Application des migrations..."
    php bin/console doctrine:migrations:migrate --env=test --no-interaction
    
    print_info "Chargement des fixtures de test..."
    php bin/console doctrine:fixtures:load --env=test --no-interaction --quiet
    
    cd ..
    print_success "Base de données de test configurée"
}

# Lancer les tests unitaires backend
run_backend_unit_tests() {
    print_header "🧪 TESTS UNITAIRES BACKEND (Symfony)"
    
    cd $BACKEND_DIR
    
    if [ "$COVERAGE" = true ]; then
        php bin/phpunit tests/Unit/ --coverage-html coverage/unit --testdox
    else
        php bin/phpunit tests/Unit/ --testdox
    fi
    
    cd ..
    print_success "Tests unitaires backend terminés"
}

# Lancer les tests unitaires frontend
run_frontend_unit_tests() {
    print_header "⚛️ TESTS UNITAIRES FRONTEND (React)"
    
    cd $FRONTEND_DIR
    
    if [ "$COVERAGE" = true ]; then
        npm run test:coverage
    elif [ "$WATCH" = true ]; then
        npm run test:watch
    else
        npm run test
    fi
    
    cd ..
    print_success "Tests unitaires frontend terminés"
}

# Lancer les tests d'intégration
run_integration_tests() {
    print_header "🔗 TESTS D'INTÉGRATION"
    
    cd $BACKEND_DIR
    
    if [ "$COVERAGE" = true ]; then
        php bin/phpunit tests/Integration/ --coverage-html coverage/integration --testdox
    else
        php bin/phpunit tests/Integration/ --testdox
    fi
    
    cd ..
    print_success "Tests d'intégration terminés"
}

# Démarrer l'application pour les tests E2E
start_application_for_e2e() {
    print_info "Démarrage de l'application pour les tests E2E..."
    
    # Démarrer les services backend et frontend
    docker-compose -f docker-compose.test.yml up -d bank-backend bank-frontend
    
    # Attendre que les services soient prêts
    print_info "Attente du démarrage de l'application..."
    
    # Attendre le backend
    timeout 60 bash -c '
    until curl -f http://localhost:8000/api/health >/dev/null 2>&1; do
        echo "Attente du backend..."
        sleep 2
    done'
    
    # Attendre le frontend
    timeout 60 bash -c '
    until curl -f http://localhost:5173 >/dev/null 2>&1; do
        echo "Attente du frontend..."
        sleep 2
    done'
    
    print_success "Application prête pour les tests E2E"
}

# Lancer les tests E2E
run_e2e_tests() {
    print_header "🎭 TESTS END-TO-END"
    
    start_application_for_e2e
    
    cd $FRONTEND_DIR
    
    # Installer Playwright si nécessaire
    if [ ! -d "node_modules/@playwright" ]; then
        print_info "Installation de Playwright..."
        npx playwright install
    fi
    
    # Lancer les tests E2E
    npm run test:e2e
    
    cd ..
    print_success "Tests E2E terminés"
}

# Générer un rapport de tests consolidé
generate_test_report() {
    print_header "📊 GÉNÉRATION DU RAPPORT DE TESTS"
    
    echo "# Rapport de Tests - $(date)" > test-report.md
    echo "" >> test-report.md
    
    # Résumé des tests backend
    if [ -f "$BACKEND_DIR/coverage/index.html" ]; then
        echo "## 📦 Tests Backend" >> test-report.md
        echo "- Couverture de code disponible dans \`$BACKEND_DIR/coverage/\`" >> test-report.md
        echo "" >> test-report.md
    fi
    
    # Résumé des tests frontend
    if [ -f "$FRONTEND_DIR/coverage/index.html" ]; then
        echo "## ⚛️ Tests Frontend" >> test-report.md
        echo "- Couverture de code disponible dans \`$FRONTEND_DIR/coverage/\`" >> test-report.md
        echo "" >> test-report.md
    fi
    
    echo "## 🎯 Recommandations" >> test-report.md
    echo "- Vérifiez les rapports de couverture pour identifier les zones non testées" >> test-report.md
    echo "- Examinez les tests qui échouent et corrigez-les avant de pousser le code" >> test-report.md
    echo "- Ajoutez des tests pour toute nouvelle fonctionnalité" >> test-report.md
    
    print_success "Rapport généré: test-report.md"
}

# Nettoyer après les tests
cleanup() {
    print_header "🧹 NETTOYAGE POST-TESTS"
    
    # Arrêter les conteneurs de test
    docker-compose -f docker-compose.test.yml down
    
    print_success "Nettoyage terminé"
}

# Traitement des arguments
UNIT_ONLY=false
INTEGRATION_ONLY=false
E2E_ONLY=false
BACKEND_ONLY=false
FRONTEND_ONLY=false
COVERAGE=false
WATCH=false
CLEAN=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --unit)
            UNIT_ONLY=true
            shift
            ;;
        --integration)
            INTEGRATION_ONLY=true
            shift
            ;;
        --e2e)
            E2E_ONLY=true
            shift
            ;;
        --backend)
            BACKEND_ONLY=true
            shift
            ;;
        --frontend)
            FRONTEND_ONLY=true
            shift
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --watch)
            WATCH=true
            shift
            ;;
        --clean)
            CLEAN=true
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            print_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Fonction principale
main() {
    print_header "🏦 TESTS APPLICATION BANCAIRE SYMFONY/REACT"
    
    # Vérifications préliminaires
    check_docker
    check_dependencies
    
    # Nettoyage si demandé
    if [ "$CLEAN" = true ]; then
        clean_environment
    fi
    
    # Démarrer les services de test
    start_test_services
    setup_test_database
    
    # Piège pour nettoyer en cas d'interruption
    trap cleanup EXIT
    
    # Exécuter les tests selon les options
    if [ "$BACKEND_ONLY" = true ]; then
        if [ "$UNIT_ONLY" = true ]; then
            run_backend_unit_tests
        elif [ "$INTEGRATION_ONLY" = true ]; then
            run_integration_tests
        else
            run_backend_unit_tests
            run_integration_tests
        fi
    elif [ "$FRONTEND_ONLY" = true ]; then
        run_frontend_unit_tests
    elif [ "$UNIT_ONLY" = true ]; then
        run_backend_unit_tests
        run_frontend_unit_tests
    elif [ "$INTEGRATION_ONLY" = true ]; then
        run_integration_tests
    elif [ "$E2E_ONLY" = true ]; then
        run_e2e_tests
    else
        # Lancer tous les tests
        run_backend_unit_tests
        run_frontend_unit_tests
        run_integration_tests
        run_e2e_tests
    fi
    
    # Générer le rapport si couverture demandée
    if [ "$COVERAGE" = true ]; then
        generate_test_report
    fi
    
    print_header "🎉 TOUS LES TESTS TERMINÉS AVEC SUCCÈS!"
    print_success "Application prête pour la production"
}

# Point d'entrée du script
main "$@"
