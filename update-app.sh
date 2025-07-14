#!/bin/bash

# update-app.sh - Script de mise à jour automatique de l'application
# Usage: ./update-app.sh [environment] [version]

set -e

ENVIRONMENT=${1:-production}
VERSION=${2:-latest}

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

echo "🔄 Mise à jour de l'application bancaire..."
echo "   Environnement: $ENVIRONMENT"
echo "   Version: $VERSION"

# Déterminer le fichier compose
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
        echo "❌ Environnement invalide: $ENVIRONMENT"
        exit 1
        ;;
esac

# Vérifier que le fichier existe
if [ ! -f "$COMPOSE_FILE" ]; then
    echo "❌ Fichier $COMPOSE_FILE introuvable"
    exit 1
fi

# Sauvegarde rapide en production
if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
    print_info "Sauvegarde rapide avant mise à jour..."
    BACKUP_DIR="backups/update_$(date +%Y%m%d_%H%M%S)"
    mkdir -p $BACKUP_DIR
    
    if docker-compose -f $COMPOSE_FILE ps mysql | grep -q Up; then
        docker-compose -f $COMPOSE_FILE exec -T mysql mysqldump \
            -u root -p${MYSQL_ROOT_PASSWORD} bank_prod > $BACKUP_DIR/pre_update.sql 2>/dev/null || \
            print_warning "Impossible de faire la sauvegarde"
    fi
fi

# Récupérer les dernières images
print_info "Récupération des dernières images..."
if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
    # En production, utiliser les images du registry
    if [ "$VERSION" = "latest" ]; then
        docker pull katekate7/bank-backend:latest
        docker pull katekate7/bank-frontend:latest
    else
        docker pull katekate7/bank-backend:$VERSION
        docker pull katekate7/bank-frontend:$VERSION
    fi
else
    # En dev/test, rebuild localement
    docker-compose -f $COMPOSE_FILE pull
fi

# Mise à jour progressive (zero-downtime pour la production)
if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
    print_info "Mise à jour progressive du backend..."
    docker-compose -f $COMPOSE_FILE up -d bank-backend
    
    print_info "Attente de la stabilisation du backend (30s)..."
    sleep 30
    
    # Vérifier que le backend fonctionne
    if curl -f https://api.bank.example.com/api/health &> /dev/null; then
        print_success "Backend mis à jour avec succès"
    else
        print_warning "Problème détecté avec le backend"
    fi
    
    print_info "Mise à jour du frontend..."
    docker-compose -f $COMPOSE_FILE up -d bank-frontend
    
    print_info "Attente de la stabilisation du frontend (30s)..."
    sleep 30
    
    if curl -f https://bank.example.com &> /dev/null; then
        print_success "Frontend mis à jour avec succès"
    else
        print_warning "Problème détecté avec le frontend"
    fi
else
    # Mise à jour standard pour dev/test
    print_info "Redémarrage de tous les services..."
    docker-compose -f $COMPOSE_FILE down
    docker-compose -f $COMPOSE_FILE up -d
    
    print_info "Attente du démarrage (30s)..."
    sleep 30
fi

# Vérification de santé finale
print_info "Vérification de santé de l'application..."

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

# Test du backend
if curl -f $BACKEND_URL/api/health &> /dev/null; then
    print_success "Backend opérationnel"
else
    print_warning "Backend non accessible"
fi

# Test du frontend
if curl -f $FRONTEND_URL &> /dev/null; then
    print_success "Frontend opérationnel"
else
    print_warning "Frontend non accessible"
fi

# Afficher l'état des services
print_info "État des services:"
docker-compose -f $COMPOSE_FILE ps

print_success "Mise à jour terminée!"

echo ""
echo "🌐 Application accessible sur:"
echo "   Frontend: $FRONTEND_URL"
echo "   Backend:  $BACKEND_URL"

if [ "$ENVIRONMENT" = "production" ] || [ "$ENVIRONMENT" = "prod" ]; then
    echo ""
    echo "📊 Pour surveiller l'application:"
    echo "   docker-compose -f $COMPOSE_FILE logs -f"
    echo ""
    echo "🔄 En cas de problème, rollback avec:"
    echo "   ./deploy.sh $ENVIRONMENT [version-précédente]"
fi
