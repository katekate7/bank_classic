#!/bin/bash

# 🔄 Script de Mise à Jour Automatique - Bank Application
# Ce script récupère les dernières images Docker et redémarre les services

set -e

# Configuration
REGISTRY="${DOCKER_REGISTRY:-docker.io}"
COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

# Couleurs
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date +'%H:%M:%S')] $1${NC}"
}

success() {
    echo -e "${GREEN}[SUCCESS] $1${NC}"
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Fonction principale de mise à jour
update_application() {
    log "🔄 Début de la mise à jour automatique..."
    
    # Récupérer les dernières images
    log "📥 Récupération des dernières images..."
    docker-compose -f "$COMPOSE_FILE" pull
    
    # Redémarrer les services avec les nouvelles images
    log "🔄 Redémarrage des services..."
    docker-compose -f "$COMPOSE_FILE" up -d
    
    # Attendre que les services soient prêts
    log "⏳ Attente de la disponibilité des services..."
    sleep 30
    
    # Vérification rapide
    if curl -f -s http://localhost:8000/health >/dev/null 2>&1; then
        success "✅ Application mise à jour avec succès!"
    else
        warning "⚠️ Application redémarrée mais health check échoué"
    fi
    
    # Nettoyage des anciennes images
    log "🧹 Nettoyage des anciennes images..."
    docker image prune -f
    
    success "🎉 Mise à jour terminée!"
}

# Vérification de l'état des services
check_status() {
    log "📊 État des services:"
    docker-compose -f "$COMPOSE_FILE" ps
}

# Point d'entrée principal
case "${1:-}" in
    "update"|"")
        update_application
        ;;
    "status")
        check_status
        ;;
    *)
        echo "Usage: $0 [update|status]"
        echo "  update  - Met à jour l'application (défaut)"
        echo "  status  - Affiche l'état des services"
        exit 1
        ;;
esac
