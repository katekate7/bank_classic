#!/bin/bash

# 🚀 Script de Déploiement Automatisé - Bank Application
# Ce script automatise le déploiement avec mise à jour des images Docker

set -e  # Arrêt en cas d'erreur

# Configuration
REGISTRY="${DOCKER_REGISTRY:-docker.io}"
IMAGE_TAG="${IMAGE_TAG:-latest}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
APP_DIR="${APP_DIR:-/opt/bank-app}"

# Couleurs pour logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

# Vérification des prérequis
check_prerequisites() {
    log "🔍 Vérification des prérequis..."
    
    command -v docker >/dev/null 2>&1 || error "Docker n'est pas installé"
    command -v docker-compose >/dev/null 2>&1 || error "Docker Compose n'est pas installé"
    
    if [[ ! -f "$COMPOSE_FILE" ]]; then
        error "Fichier $COMPOSE_FILE introuvable"
    fi
    
    success "Prérequis validés"
}

# Sauvegarde avant déploiement
backup_current_state() {
    log "💾 Sauvegarde de l'état actuel..."
    
    # Backup de la base de données
    docker-compose -f "$COMPOSE_FILE" exec -T db mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" mybank > "backup_$(date +%Y%m%d_%H%M%S).sql" 2>/dev/null || warning "Impossible de sauvegarder la DB"
    
    # Backup de la configuration
    cp "$COMPOSE_FILE" "${COMPOSE_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
    
    success "Sauvegarde terminée"
}

# Récupération des dernières images
pull_latest_images() {
    log "🐳 Récupération des dernières images Docker..."
    
    local backend_image="${REGISTRY}/bank-backend:${IMAGE_TAG}"
    local frontend_image="${REGISTRY}/bank-frontend:${IMAGE_TAG}"
    
    docker pull "$backend_image" || error "Impossible de récupérer l'image backend"
    docker pull "$frontend_image" || error "Impossible de récupérer l'image frontend"
    
    success "Images récupérées avec succès"
}

# Health check de l'application
health_check() {
    log "🏥 Vérification de la santé de l'application..."
    
    local max_attempts=30
    local attempt=1
    
    while [[ $attempt -le $max_attempts ]]; do
        log "Tentative $attempt/$max_attempts..."
        
        # Check backend
        if curl -f -s http://localhost:8000/health >/dev/null 2>&1; then
            success "Backend opérationnel"
            break
        fi
        
        if [[ $attempt -eq $max_attempts ]]; then
            error "Backend non accessible après $max_attempts tentatives"
        fi
        
        sleep 10
        ((attempt++))
    done
    
    # Check frontend
    if curl -f -s http://localhost:5173/ >/dev/null 2>&1; then
        success "Frontend opérationnel"
    else
        warning "Frontend possiblement non accessible"
    fi
}

# Mise à jour rolling avec zéro downtime
rolling_update() {
    log "🔄 Déploiement rolling update..."
    
    # Mise à jour du backend
    log "Mise à jour du backend..."
    docker-compose -f "$COMPOSE_FILE" up -d --no-deps backend
    
    # Attendre que le backend soit prêt
    sleep 20
    
    # Mise à jour du frontend
    log "Mise à jour du frontend..."
    docker-compose -f "$COMPOSE_FILE" up -d --no-deps frontend
    
    success "Rolling update terminé"
}

# Nettoyage des ressources
cleanup() {
    log "🧹 Nettoyage des ressources..."
    
    # Suppression des images non utilisées
    docker image prune -f
    
    # Suppression des containers arrêtés
    docker container prune -f
    
    # Suppression des volumes anonymes
    docker volume prune -f
    
    success "Nettoyage terminé"
}

# Rollback en cas d'échec
rollback() {
    error "❌ Échec du déploiement, rollback en cours..."
    
    # Restaurer la configuration précédente
    local backup_file=$(ls -t "${COMPOSE_FILE}.backup."* 2>/dev/null | head -n1)
    if [[ -n "$backup_file" ]]; then
        cp "$backup_file" "$COMPOSE_FILE"
        docker-compose -f "$COMPOSE_FILE" up -d
        warning "Rollback effectué vers la version précédente"
    else
        error "Impossible de faire le rollback - aucune sauvegarde trouvée"
    fi
}

# Fonction principale
main() {
    log "🚀 Début du déploiement automatisé de Bank Application"
    log "Registry: $REGISTRY"
    log "Image Tag: $IMAGE_TAG"
    log "Compose File: $COMPOSE_FILE"
    
    # Trap pour rollback en cas d'erreur
    trap rollback ERR
    
    check_prerequisites
    backup_current_state
    pull_latest_images
    rolling_update
    health_check
    cleanup
    
    success "🎉 Déploiement réussi! Application mise à jour avec succès."
    
    # Afficher les informations de déploiement
    log "📊 Informations de déploiement:"
    docker-compose -f "$COMPOSE_FILE" ps
}

# Gestion des arguments
case "${1:-}" in
    "rollback")
        rollback
        ;;
    "health")
        health_check
        ;;
    "cleanup")
        cleanup
        ;;
    *)
        main "$@"
        ;;
esac
