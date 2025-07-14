#!/bin/bash

# deploy.sh - Script de déploiement automatisé
# Usage: ./deploy.sh [environment] [version]
# Environments: development, test, production
# Version: latest, v1.0.0, commit-hash, etc.

set -e

# Configuration
ENVIRONMENT=${1:-development}
VERSION=${2:-latest}
DOCKER_USERNAME=${DOCKER_USERNAME:-katekate7}

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Affichage de l'aide
show_help() {
    echo "Script de déploiement pour l'application bancaire"
    echo ""
    echo "Usage: $0 [ENVIRONMENT] [VERSION]"
    echo ""
    echo "ENVIRONMENT:"
    echo "  development, dev    Environnement de développement (défaut)"
    echo "  test, testing       Environnement de test"
    echo "  production, prod    Environnement de production"
    echo ""
    echo "VERSION:"
    echo "  latest             Version la plus récente (défaut)"
    echo "  v1.0.0             Version spécifique"
    echo "  commit-hash        Hash de commit Git"
    echo ""
    echo "Exemples:"
    echo "  $0                          # Déploiement en développement"
    echo "  $0 production latest        # Déploiement en production"
    echo "  $0 test v1.2.0             # Déploiement de test avec version"
    echo ""
    echo "Variables d'environnement:"
    echo "  DOCKER_USERNAME     Nom d'utilisateur Docker Hub"
    echo "  MYSQL_ROOT_PASSWORD Mot de passe MySQL (production)"
    echo "  JWT_SECRET          Clé secrète JWT (production)"
}

# Validation de l'environnement
validate_environment() {
    print_info "Validation de l'environnement: $ENVIRONMENT"
    
    case $ENVIRONMENT in
        development|dev)
            COMPOSE_FILE="docker-compose.yml"
            ;;
        test|testing)
            COMPOSE_FILE="docker-compose.test.yml"
            ;;
        production|prod)
            COMPOSE_FILE="docker-compose.prod.yml"
            # Vérifier les variables requises pour la production
            if [ -z "$MYSQL_ROOT_PASSWORD" ] || [ -z "$JWT_SECRET" ]; then
                print_error "Variables d'environnement manquantes pour la production"
                echo "Requises: MYSQL_ROOT_PASSWORD, JWT_SECRET"
                exit 1
            fi
            ;;
        help|--help|-h)
            show_help
            exit 0
            ;;
        *)
            print_error "Environnement invalide: $ENVIRONMENT"
            echo "Environnements supportés: development, test, production"
            show_help
            exit 1
            ;;
    esac
    
    print_success "Environnement validé: $ENVIRONMENT"
}

# Vérifications pré-déploiement
pre_deployment_checks() {
    print_header "🔍 VÉRIFICATIONS PRÉ-DÉPLOIEMENT"
    
    # Vérifier Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker n'est pas installé"
        exit 1
    fi
    print_success "Docker installé"
    
    # Vérifier Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose n'est pas installé"
        exit 1
    fi
    print_success "Docker Compose installé"
    
    # Vérifier que Docker fonctionne
    if ! docker info &> /dev/null; then
        print_error "Docker n'est pas en cours d'exécution"
        exit 1
    fi
    print_success "Docker en cours d'exécution"
    
    # Vérifier que le fichier compose existe
    if [ ! -f "$COMPOSE_FILE" ]; then
        print_error "Fichier $COMPOSE_FILE introuvable"
        exit 1
    fi
    print_success "Fichier de configuration Docker Compose trouvé"
    
    # Vérifier l'espace disque disponible
    AVAILABLE_SPACE=$(df . | awk 'NR==2{print $4}')
    if [ "$AVAILABLE_SPACE" -lt 1048576 ]; then  # 1GB en KB
        print_warning "Espace disque faible (< 1GB)"
    else
        print_success "Espace disque suffisant"
    fi
}

# Tests avant déploiement
run_pre_deployment_tests() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        print_header "🧪 TESTS AVANT DÉPLOIEMENT EN PRODUCTION"
        
        print_info "Exécution des tests critiques..."
        
        # Vérifier que le script de test existe
        if [ ! -f "./run-tests.sh" ]; then
            print_error "Script de test introuvable"
            exit 1
        fi
        
        # Lancer les tests unitaires et d'intégration
        if ! ./run-tests.sh --unit --integration; then
            print_error "Les tests ont échoué, déploiement annulé"
            exit 1
        fi
        
        print_success "Tests pré-déploiement réussis"
    else
        print_info "Tests pré-déploiement ignorés (environnement: $ENVIRONMENT)"
    fi
}

# Sauvegarde (pour la production)
backup_production() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        print_header "💾 SAUVEGARDE DE PRODUCTION"
        
        # Créer un répertoire de sauvegarde avec timestamp
        BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
        mkdir -p $BACKUP_DIR
        print_info "Répertoire de sauvegarde créé: $BACKUP_DIR"
        
        # Vérifier si MySQL est en cours d'exécution
        if docker-compose -f $COMPOSE_FILE ps mysql | grep -q Up; then
            print_info "Sauvegarde de la base de données..."
            
            # Sauvegarder la base de données
            docker-compose -f $COMPOSE_FILE exec -T mysql mysqldump \
                -u root -p${MYSQL_ROOT_PASSWORD} bank_prod \
                > $BACKUP_DIR/database.sql 2>/dev/null || {
                print_warning "Impossible de sauvegarder la base de données"
            }
            
            # Sauvegarder les volumes Docker
            docker-compose -f $COMPOSE_FILE exec -T mysql tar czf - /var/lib/mysql \
                > $BACKUP_DIR/mysql_data.tar.gz 2>/dev/null || {
                print_warning "Impossible de sauvegarder les données MySQL"
            }
            
            print_success "Sauvegarde créée: $BACKUP_DIR"
        else
            print_info "MySQL non démarré, sauvegarde ignorée"
        fi
    fi
}

# Construction des images
build_images() {
    print_header "🏗️ CONSTRUCTION DES IMAGES DOCKER"
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        print_info "Récupération des images de production depuis Docker Hub..."
        
        # Définir les tags selon la version
        if [ "$VERSION" = "latest" ]; then
            BACKEND_TAG="latest"
            FRONTEND_TAG="latest"
        else
            BACKEND_TAG="$VERSION"
            FRONTEND_TAG="$VERSION"
        fi
        
        # Récupérer les images
        docker pull $DOCKER_USERNAME/bank-backend:$BACKEND_TAG || {
            print_warning "Image backend $BACKEND_TAG non trouvée, utilisation de latest"
            docker pull $DOCKER_USERNAME/bank-backend:latest
        }
        
        docker pull $DOCKER_USERNAME/bank-frontend:$FRONTEND_TAG || {
            print_warning "Image frontend $FRONTEND_TAG non trouvée, utilisation de latest"
            docker pull $DOCKER_USERNAME/bank-frontend:latest
        }
        
        print_success "Images de production récupérées"
    else
        print_info "Construction locale des images..."
        
        # Construction locale pour dev/test
        docker-compose -f $COMPOSE_FILE build --no-cache
        
        print_success "Images construites localement"
    fi
}

# Déploiement de l'application
deploy_application() {
    print_header "🚀 DÉPLOIEMENT DE L'APPLICATION"
    
    print_info "Arrêt des services existants..."
    docker-compose -f $COMPOSE_FILE down --remove-orphans
    
    print_info "Nettoyage des conteneurs arrêtés..."
    docker container prune -f
    
    print_info "Démarrage des nouveaux services..."
    docker-compose -f $COMPOSE_FILE up -d
    
    print_info "Attente du démarrage des services..."
    sleep 30
    
    print_success "Services déployés"
}

# Migration de base de données
run_migrations() {
    print_header "🗄️ MIGRATIONS DE BASE DE DONNÉES"
    
    # Attendre que MySQL soit prêt
    print_info "Attente de la disponibilité de MySQL..."
    timeout 60 bash -c '
    until docker-compose -f '"$COMPOSE_FILE"' exec mysql mysqladmin ping --silent; do
        echo "Attente de MySQL..."
        sleep 2
    done' || {
        print_warning "MySQL non accessible, migrations ignorées"
        return
    }
    
    print_info "Application des migrations..."
    docker-compose -f $COMPOSE_FILE exec -T bank-backend \
        php bin/console doctrine:migrations:migrate --no-interaction || {
        print_warning "Erreur lors des migrations"
    }
    
    # Charger les fixtures en environnement de test
    if [ "$ENVIRONMENT" = "test" ] || [ "$ENVIRONMENT" = "testing" ]; then
        print_info "Chargement des fixtures de test..."
        docker-compose -f $COMPOSE_FILE exec -T bank-backend \
            php bin/console doctrine:fixtures:load --no-interaction --env=test || {
            print_warning "Erreur lors du chargement des fixtures"
        }
    fi
    
    print_success "Migrations terminées"
}

# Vérifications de santé
health_check() {
    print_header "🏥 VÉRIFICATIONS DE SANTÉ"
    
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
    
    print_info "Vérification du backend ($BACKEND_URL)..."
    BACKEND_RETRY=0
    while [ $BACKEND_RETRY -lt 10 ]; do
        if curl -f $BACKEND_URL/api/health &> /dev/null; then
            print_success "Backend accessible"
            break
        else
            BACKEND_RETRY=$((BACKEND_RETRY + 1))
            if [ $BACKEND_RETRY -eq 10 ]; then
                print_warning "Backend non accessible après 10 tentatives"
            else
                print_info "Tentative $BACKEND_RETRY/10..."
                sleep 5
            fi
        fi
    done
    
    print_info "Vérification du frontend ($FRONTEND_URL)..."
    FRONTEND_RETRY=0
    while [ $FRONTEND_RETRY -lt 10 ]; do
        if curl -f $FRONTEND_URL &> /dev/null; then
            print_success "Frontend accessible"
            break
        else
            FRONTEND_RETRY=$((FRONTEND_RETRY + 1))
            if [ $FRONTEND_RETRY -eq 10 ]; then
                print_warning "Frontend non accessible après 10 tentatives"
            else
                print_info "Tentative $FRONTEND_RETRY/10..."
                sleep 5
            fi
        fi
    done
    
    # Vérifier les conteneurs
    print_info "État des conteneurs:"
    docker-compose -f $COMPOSE_FILE ps
}

# Tests post-déploiement
post_deployment_tests() {
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        print_header "🧪 TESTS POST-DÉPLOIEMENT"
        
        print_info "Exécution des tests de fumée..."
        
        # Tests basiques de l'API
        if curl -f $BACKEND_URL/api/health &> /dev/null; then
            print_success "API de santé fonctionne"
        else
            print_error "API de santé non accessible"
        fi
        
        # Test de connexion à la base de données
        if docker-compose -f $COMPOSE_FILE exec -T bank-backend \
           php bin/console doctrine:query:sql "SELECT 1" &> /dev/null; then
            print_success "Connexion base de données OK"
        else
            print_warning "Problème de connexion base de données"
        fi
        
        print_success "Tests post-déploiement terminés"
    fi
}

# Affichage du résumé
display_summary() {
    print_header "🎉 DÉPLOIEMENT TERMINÉ"
    
    echo ""
    echo "📊 Résumé du déploiement:"
    echo "   • Environnement: $ENVIRONMENT"
    echo "   • Version: $VERSION"
    echo "   • Fichier Docker Compose: $COMPOSE_FILE"
    echo ""
    
    echo "🌐 URLs de l'application:"
    case $ENVIRONMENT in
        development|dev)
            echo "   • Frontend: http://localhost:5173"
            echo "   • Backend:  http://localhost:8000"
            echo "   • API Doc:  http://localhost:8000/api/doc"
            echo "   • Adminer:  http://localhost:8080"
            ;;
        test|testing)
            echo "   • Frontend: http://localhost:5174"
            echo "   • Backend:  http://localhost:8001"
            echo "   • Tests:    ./run-tests.sh"
            ;;
        production|prod)
            echo "   • Frontend: https://bank.example.com"
            echo "   • Backend:  https://api.bank.example.com"
            echo "   • Monitoring: https://monitoring.bank.example.com"
            ;;
    esac
    
    echo ""
    echo "🔧 Commandes utiles:"
    echo "   • Voir les logs: docker-compose -f $COMPOSE_FILE logs -f"
    echo "   • État des services: docker-compose -f $COMPOSE_FILE ps"
    echo "   • Arrêter: docker-compose -f $COMPOSE_FILE down"
    echo "   • Redémarrer: docker-compose -f $COMPOSE_FILE restart"
    
    if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
        echo ""
        echo "🔄 Pour rollback en cas de problème:"
        echo "   • ./deploy.sh production [version-précédente]"
        echo "   • Ou: docker-compose -f $COMPOSE_FILE down && docker-compose -f $COMPOSE_FILE up -d"
    fi
}

# Fonction de nettoyage
cleanup() {
    if [ $? -ne 0 ]; then
        print_error "Erreur lors du déploiement"
        print_info "Nettoyage en cours..."
        
        # Afficher les logs en cas d'erreur
        echo ""
        echo "📋 Logs des services:"
        docker-compose -f $COMPOSE_FILE logs --tail=50
    fi
}

# Fonction principale
main() {
    # Piège pour nettoyer en cas d'erreur
    trap cleanup EXIT
    
    print_header "🏦 DÉPLOIEMENT APPLICATION BANCAIRE SYMFONY/REACT"
    
    validate_environment
    pre_deployment_checks
    run_pre_deployment_tests
    backup_production
    build_images
    deploy_application
    run_migrations
    health_check
    post_deployment_tests
    display_summary
    
    # Désactiver le piège si tout s'est bien passé
    trap - EXIT
}

# Point d'entrée du script
if [ "$1" = "--help" ] || [ "$1" = "-h" ] || [ "$1" = "help" ]; then
    show_help
    exit 0
fi

main "$@"
