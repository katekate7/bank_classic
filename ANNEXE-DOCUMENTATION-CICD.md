# ANNEXE - Documentation CI/CD
## Application Bancaire - Symfony & React

---

## 📦 Installation de Docker

### Pourquoi Docker ?
Docker est utilisé dans ce projet pour **conteneuriser** l'application, ce qui signifie :
- **Portabilité** : L'application fonctionne de manière identique sur tous les environnements
- **Isolement** : Chaque composant (frontend, backend, base de données) fonctionne dans son propre conteneur
- **Simplicité** : Une seule commande pour démarrer toute l'application
- **Cohérence** : Les mêmes versions de logiciels sont utilisées partout (développement, test, production)

### Instructions d'installation

#### Windows
1. Télécharger **Docker Desktop** depuis https://www.docker.com/products/docker-desktop
2. Exécuter le fichier d'installation téléchargé
3. Redémarrer l'ordinateur si demandé
4. Vérifier l'installation :
   ```bash
   docker --version
   docker compose version
   ```

#### macOS
1. Télécharger **Docker Desktop** depuis https://www.docker.com/products/docker-desktop
2. Glisser Docker dans le dossier Applications
3. Lancer Docker Desktop depuis le Launchpad
4. Vérifier l'installation :
   ```bash
   docker --version
   docker compose version
   ```

#### Linux (Ubuntu/Debian)
1. Mettre à jour les paquets :
   ```bash
   sudo apt update
   ```
2. Installer Docker :
   ```bash
   sudo apt install docker.io docker-compose-plugin
   ```
3. Ajouter l'utilisateur au groupe docker :
   ```bash
   sudo usermod -aG docker $USER
   ```
4. Redémarrer la session
5. Vérifier l'installation :
   ```bash
   docker --version
   docker compose version
   ```

---

## 🔄 Pipeline CI/CD avec GitHub Actions

### Qu'est-ce qu'un pipeline CI/CD ?
Un **pipeline CI/CD** (Continuous Integration/Continuous Deployment) est un processus automatisé qui :
- **CI (Intégration Continue)** : Vérifie automatiquement que le nouveau code fonctionne bien avec l'existant
- **CD (Déploiement Continu)** : Met à jour automatiquement l'application en production après validation

**Rôle dans le projet** : À chaque modification du code, le pipeline s'assure que l'application fonctionne correctement avant de la déployer automatiquement.

### Structure du fichier CI/CD (`.github/workflows/ci-cd.yml`)

#### 1. **Déclenchement automatique**
```yaml
on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]
```
Le pipeline se lance automatiquement à chaque :
- Modification sur la branche principale (`main`)
- Demande de fusion de code (`pull request`)

#### 2. **Installation des dépendances**
```yaml
- name: Set up Docker Buildx
  uses: docker/setup-buildx-action@v3
```
- Installation de Docker dans l'environnement de test GitHub
- Préparation des outils nécessaires pour construire l'application

#### 3. **Vérification du code et des tests**
```yaml
- name: 🧪 Run integration tests - Backend
  run: docker compose -f docker-compose.test.yml run --rm backend-test

- name: 🧪 Run integration tests - Frontend
  run: docker compose -f docker-compose.test.yml run --rm frontend-test npm test
```
**Étapes de vérification :**
- **Tests Backend** : Vérification que l'API Symfony fonctionne correctement
- **Tests Frontend** : Vérification que l'interface React fonctionne correctement
- **Tests d'intégration** : Vérification que frontend et backend communiquent bien ensemble

#### 4. **Déploiement automatique**
```yaml
- name: 🚀 Deploy with Docker Compose
  run: |
    docker compose -f docker-compose.prod.yml pull
    docker compose -f docker-compose.prod.yml up -d --force-recreate
```
**Étapes de déploiement :**
- Récupération des dernières versions des conteneurs
- Redémarrage de l'application en production
- Mise à jour automatique sans interruption de service

---

## 🚀 Déploiement Continu

### Processus de mise à jour automatique

#### Comment ça fonctionne ?
1. **Développeur** : Modifie le code et l'envoie sur GitHub
2. **GitHub Actions** : Détecte automatiquement le changement
3. **Tests automatiques** : Vérifie que tout fonctionne correctement
4. **Déploiement** : Si les tests passent, met à jour le serveur automatiquement
5. **Application mise à jour** : Les utilisateurs voient les nouvelles fonctionnalités

#### Avantages du déploiement automatique
- **Rapidité** : Nouvelle version disponible en quelques minutes
- **Fiabilité** : Aucun déploiement si les tests échouent
- **Traçabilité** : Historique complet de tous les déploiements
- **Rollback facile** : Retour à la version précédente en cas de problème

#### Sécurité du processus
- **Tests obligatoires** : Impossible de déployer sans valider les tests
- **Environnements séparés** : Test d'abord, production ensuite
- **Sauvegarde automatique** : Backup avant chaque mise à jour

---

## 🧪 Tests d'Intégration

### Qu'est-ce que les tests d'intégration ?
Les **tests d'intégration** vérifient que toutes les parties de l'application fonctionnent correctement **ensemble** :
- **Frontend React** ↔ **Backend Symfony** ↔ **Base de données MySQL**

### Tests implémentés dans le projet

#### 1. **Test d'intégration Backend** (`DatabaseIntegrationTest.php`)
```php
public function testExpenseCreationWithCategoryAndUser(): void
{
    // Création d'un utilisateur, d'une catégorie et d'une dépense
    // Vérification que tout est correctement sauvegardé en base
}
```
**Ce qui est testé :**
- Création d'utilisateurs dans la base de données
- Ajout de dépenses avec catégories
- Persistance des données
- Relations entre entités (utilisateur ↔ dépense ↔ catégorie)

#### 2. **Test d'intégration Frontend** (`App.test.jsx`)
```javascript
it('renders login form without crashing', () => {
    render(<LoginForm setAuthenticated={() => {}} />)
    expect(screen.getByText('Welcome to MyBank')).toBeInTheDocument()
})
```
**Ce qui est testé :**
- Affichage correct des composants React
- Formulaires de connexion et d'ajout de dépenses
- Navigation entre les pages
- Interaction avec l'API backend

#### 3. **Test de bout en bout** (Frontend → Backend → Database)
Le test `testAddExpenseFromFrontendToDatabase` simule :
1. **Utilisateur** saisit une nouvelle dépense sur l'interface
2. **Frontend** envoie les données à l'API backend
3. **Backend** traite et sauvegarde en base de données
4. **Vérification** que la dépense est bien créée

### Exécution des tests

#### En local
```bash
# Tests backend
docker compose -f docker-compose.test.yml run --rm backend-test

# Tests frontend
docker compose -f docker-compose.test.yml run --rm frontend-test npm test
```

#### Dans le pipeline CI/CD
Les tests s'exécutent automatiquement à chaque modification du code :
- ✅ **Tests passent** → Déploiement automatique
- ❌ **Tests échouent** → Arrêt du processus, correction nécessaire

### Avantages des tests d'intégration
- **Détection précoce** des problèmes entre composants
- **Assurance qualité** avant mise en production
- **Régression évitée** : Les anciennes fonctionnalités continuent de marcher
- **Confiance** dans les déploiements automatiques

---

## 📋 Résumé des Outils

| Outil | Rôle | Avantage |
|-------|------|----------|
| **Docker** | Conteneurisation | Application identique partout |
| **GitHub Actions** | Automatisation CI/CD | Tests et déploiement automatiques |
| **PHPUnit** | Tests backend | Vérification de l'API Symfony |
| **Vitest** | Tests frontend | Vérification de l'interface React |
| **Docker Compose** | Orchestration | Gestion de tous les services |

---

## 🔧 Commandes Utiles

### Développement local
```bash
# Démarrer l'application
docker compose up -d

# Voir les logs
docker compose logs -f

# Arrêter l'application
docker compose down
```

### Tests
```bash
# Tests complets
docker compose -f docker-compose.test.yml up --abort-on-container-exit

# Tests backend uniquement
docker compose -f docker-compose.test.yml run --rm backend-test

# Tests frontend uniquement
docker compose -f docker-compose.test.yml run --rm frontend-test npm test
```

### Déploiement
```bash
# Déploiement production
./deploy.sh production

# Mise à jour application
./update-app.sh production latest
```

---

*Cette documentation présente un système CI/CD moderne et robuste, garantissant la qualité et la fiabilité des déploiements de l'application bancaire.*
