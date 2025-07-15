#!/bin/bash

# Script de déploiement production rapide
# Usage: ./deploy-prod.sh [version]

set -e

VERSION=${1:-latest}
ENV_FILE=".env.prod"

echo "🚀 Déploiement en production - Version: $VERSION"

# Vérifier que le fichier d'environnement existe
if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Fichier $ENV_FILE manquant"
    echo "💡 Copiez .env.prod.example vers .env.prod et configurez-le"
    exit 1
fi

# Charger les variables d'environnement
export $(cat $ENV_FILE | grep -v '^#' | xargs)
export TAG=$VERSION

echo "📦 Arrêt des services existants..."
docker-compose -f docker-compose.prod.yml --env-file $ENV_FILE down

echo "🔄 Récupération des dernières images..."
docker-compose -f docker-compose.prod.yml --env-file $ENV_FILE pull

echo "🏗️ Démarrage des services..."
docker-compose -f docker-compose.prod.yml --env-file $ENV_FILE up -d

echo "⏳ Attente du démarrage des services..."
sleep 30

echo "🗄️ Application des migrations..."
docker-compose -f docker-compose.prod.yml --env-file $ENV_FILE exec -T bank-backend \
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "🏥 Vérification de santé..."
if curl -f http://localhost:8000/api/health &> /dev/null; then
    echo "✅ Backend opérationnel"
else
    echo "⚠️ Backend non accessible"
fi

if curl -f http://localhost &> /dev/null; then
    echo "✅ Frontend opérationnel"
else
    echo "⚠️ Frontend non accessible"
fi

echo ""
echo "🎉 Déploiement terminé!"
echo "🌐 Application accessible sur:"
echo "   Frontend: http://localhost"
echo "   Backend:  http://localhost:8000"
echo "   Adminer:  http://localhost:8080"
echo ""
echo "📊 Surveiller avec: docker-compose -f docker-compose.prod.yml logs -f"
