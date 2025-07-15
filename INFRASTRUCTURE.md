# Guide d'Installation et de Déploiement - Application Bancaire

## 📋 Vue d'ensemble

Ce guide explique comment installer, configurer et déployer l'application bancaire en utilisant Docker et GitHub Actions. L'application utilise une architecture moderne avec conteneurisation complète et pipeline CI/CD automatisé.

## 🐳 Installation de Docker

### Windows

#### Prérequis
- Windows 10 64-bit: Pro, Enterprise, ou Education (Build 16299 ou plus récent)
- Virtualisation activée dans le BIOS
- WSL 2 (Windows Subsystem for Linux)

#### Installation
1. **Télécharger Docker Desktop**
   ```
   https://docs.docker.com/desktop/windows/install/
   ```

2. **Installer Docker Desktop**
   - Exécuter le fichier `Docker Desktop Installer.exe`
   - Suivre l'assistant d'installation
   - Redémarrer l'ordinateur si demandé

3. **Vérifier l'installation**
   ```powershell
   docker --version
   docker-compose --version
   ```

#### Configuration WSL 2
```powershell
# Activer WSL 2
wsl --install

# Définir WSL 2 comme version par défaut
wsl --set-default-version 2
```

### macOS

#### Prérequis
- macOS 10.15 ou plus récent
- 4 GB de RAM minimum

#### Installation
1. **Télécharger Docker Desktop**
   ```
   https://docs.docker.com/desktop/mac/install/
   ```

2. **Installer Docker Desktop**
   - Glisser `Docker.app` vers le dossier Applications
   - Lancer Docker Desktop depuis le Launchpad
   - Suivre le processus de configuration initial

3. **Vérifier l'installation**
   ```bash
   docker --version
   docker-compose --version
   ```

### Linux (Ubuntu/Debian)

#### Installation via script automatique
```bash
# Télécharger et exécuter le script d'installation officiel
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ajouter votre utilisateur au groupe docker
sudo usermod -aG docker $USER

# Redémarrer la session ou exécuter
newgrp docker
```

#### Installation manuelle
```bash
# Mettre à jour les paquets
sudo apt-get update

# Installer les prérequis
sudo apt-get install \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# Ajouter la clé GPG officielle de Docker
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Ajouter le repository Docker
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Installer Docker Engine
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-compose-plugin
```

#### Installer Docker Compose
```bash
# Télécharger Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose

# Rendre exécutable
sudo chmod +x /usr/local/bin/docker-compose

# Vérifier l'installation
docker-compose --version
```

## 🚀 Pourquoi Docker dans ce projet ?

### Avantages de la conteneurisation

1. **Cohérence des environnements**
   - Même environnement en développement, test et production
   - Élimine les problèmes "ça marche sur ma machine"
   - Configuration reproductible et versionnable

2. **Isolation des services**
   - Frontend, Backend et Base de données dans des conteneurs séparés
   - Pas d'interférence entre les dépendances
   - Sécurité renforcée par l'isolation

3. **Facilité de déploiement**
   - Déploiement simple avec `docker-compose up`
   - Mise à l'échelle facile des services
   - Rollback rapide en cas de problème

4. **Gestion des dépendances**
   - PHP, Node.js, MySQL encapsulés dans les conteneurs
   - Pas besoin d'installer localement
   - Versions fixées et contrôlées

### Architecture Docker de l'application

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │   Database      │
│   (React)       │    │   (Symfony)     │    │   (MySQL)       │
│   Port: 5173    │◄──►│   Port: 8000    │◄──►│   Port: 3306    │
│                 │    │                 │    │                 │
│ • Node.js 18    │    │ • PHP 8.2       │    │ • MySQL 8.0     │
│ • Vite          │    │ • Apache        │    │ • Persistence   │
│ • NPM packages  │    │ • Composer      │    │ • Volumes       │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 🔄 Pipeline CI/CD avec GitHub Actions

### Qu'est-ce qu'un pipeline CI/CD ?

**CI (Intégration Continue)** : Processus automatique qui vérifie le code à chaque modification
**CD (Déploiement Continu)** : Processus automatique qui déploie le code validé

### Notre pipeline en 6 étapes

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  1. Tests   │───►│ 2. Build    │───►│ 3. Deploy  │
│  Backend    │    │  Docker     │    │ Production │
└─────────────┘    │  Images     │    └─────────────┘
                   └─────────────┘
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  4. Tests   │───►│ 5. Sécurité │───►│ 6. Notify   │
│  Frontend   │    │  Scan       │    │  Results    │
└─────────────┘    └─────────────┘    └─────────────┘
```

### Étapes détaillées du pipeline

#### 1. **Installation des dépendances**
```yaml
# Backend - Composer
- name: Install backend dependencies
  run: composer install --optimize-autoloader

# Frontend - NPM
- name: Install frontend dependencies
  run: npm ci
```

#### 2. **Vérification du code et tests**
```yaml
# Tests unitaires PHP
- name: Run backend tests
  run: php bin/phpunit --coverage-clover coverage.xml

# Tests JavaScript/React
- name: Run frontend tests
  run: npm run test:coverage

# Linting et formatage
- name: Lint code
  run: npm run lint
```

#### 3. **Tests d'intégration**
```yaml
# Base de données de test
- name: Setup test database
  run: |
    php bin/console doctrine:database:create --env=test
    php bin/console doctrine:migrations:migrate --env=test

# Tests d'intégration complets
- name: Run integration tests
  run: php bin/phpunit tests/Integration/
```

#### 4. **Construction des images Docker**
```yaml
# Construction et push vers Docker Hub
- name: Build and push Docker images
  run: |
    docker build -t katekate7/bank-backend:latest ./bank-backend
    docker build -t katekate7/bank-frontend:latest ./bank-frontend
    docker push katekate7/bank-backend:latest
    docker push katekate7/bank-frontend:latest
```

#### 5. **Analyse de sécurité**
```yaml
# Scan des vulnérabilités
- name: Security scan
  run: |
    composer audit
    npm audit
    trivy image katekate7/bank-backend:latest
```

#### 6. **Déploiement**
```yaml
# Déploiement automatisé
- name: Deploy to production
  run: |
    docker-compose -f docker-compose.prod.yml up -d
    docker-compose exec backend php bin/console cache:clear --env=prod
```

## 🔄 Déploiement Continu

### Comment l'application se met à jour automatiquement

1. **Déclenchement automatique**
   - Push sur la branche `main` → Déclenche le pipeline
   - Pull Request → Tests automatiques
   - Tag de version → Déploiement en production

2. **Processus de mise à jour**
   ```bash
   # Le serveur exécute automatiquement :
   git pull origin main
   docker-compose pull
   docker-compose up -d --build
   docker-compose exec backend php bin/console doctrine:migrations:migrate --no-interaction
   ```

3. **Vérification post-déploiement**
   ```bash
   # Tests de santé automatiques
   curl -f http://localhost:8000/api/health
   curl -f http://localhost:5173/
   ```

4. **Rollback automatique**
   - Si les tests de santé échouent
   - Retour automatique à la version précédente
   - Notification des administrateurs

### Script de mise à jour automatique

Notre script `update-app.sh` :

```bash
#!/bin/bash
# Récupère les dernières images Docker
docker-compose pull

# Redémarre les services avec les nouvelles images
docker-compose up -d

# Exécute les migrations de base de données
docker-compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

# Nettoie les anciennes images
docker image prune -f
```

## 🧪 Tests d'Intégration

### Qu'est-ce que les tests d'intégration ?

Les tests d'intégration vérifient que **toutes les parties de l'application fonctionnent bien ensemble** :

- **Frontend** ↔ **Backend** : Communication API
- **Backend** ↔ **Base de données** : Stockage des données
- **Ensemble du système** : Parcours utilisateur complets

### Exemples de tests d'intégration

#### Test 1 : Création d'une dépense complète
```
👤 Utilisateur se connecte
   ↓
📝 Saisit une nouvelle dépense via le frontend
   ↓
🚀 Frontend envoie les données au backend via API
   ↓
💾 Backend valide et stocke en base de données
   ↓
✅ Frontend affiche la confirmation
```

#### Test 2 : Modification d'une dépense
```
📋 Frontend récupère la liste des dépenses
   ↓
✏️ Utilisateur modifie une dépense
   ↓
🔄 Backend met à jour la base de données
   ↓
📊 Frontend affiche les données mises à jour
```

#### Test 3 : Suppression et persistance
```
🗑️ Utilisateur supprime une dépense
   ↓
❌ Backend supprime de la base de données
   ↓
🔄 Frontend met à jour l'affichage
   ↓
✅ Vérification : la dépense n'existe plus
```

### Tests automatisés dans notre application

#### Backend (PHPUnit)
```php
public function testCompleteExpenseCreationFlow()
{
    // 1. Créer un utilisateur
    $user = $this->createTestUser();
    
    // 2. Authentification
    $this->client->loginUser($user);
    
    // 3. Créer une dépense via API
    $this->client->request('POST', '/api/expenses', [], [], 
        ['CONTENT_TYPE' => 'application/json'],
        json_encode(['amount' => 25.50, 'description' => 'Test'])
    );
    
    // 4. Vérifier la réponse
    $this->assertResponseStatusCodeSame(201);
    
    // 5. Vérifier en base de données
    $expense = $this->entityManager
        ->getRepository(Expense::class)
        ->findOneBy(['description' => 'Test']);
    $this->assertNotNull($expense);
}
```

#### Frontend (Vitest + Testing Library)
```javascript
test('should create expense and update UI', async () => {
  // 1. Rendre le composant
  const user = userEvent.setup()
  render(<ExpenseForm />)
  
  // 2. Remplir le formulaire
  await user.type(screen.getByLabelText(/amount/i), '25.50')
  await user.type(screen.getByLabelText(/description/i), 'Test expense')
  
  // 3. Soumettre
  await user.click(screen.getByRole('button', { name: /save/i }))
  
  // 4. Vérifier l'appel API
  expect(mockCreateExpense).toHaveBeenCalledWith({
    amount: 25.50,
    description: 'Test expense'
  })
})
```

#### E2E (Playwright)
```javascript
test('complete user journey', async ({ page }) => {
  // 1. Connexion
  await page.goto('/login')
  await page.fill('[data-testid="email"]', 'test@example.com')
  await page.fill('[data-testid="password"]', 'password')
  await page.click('[data-testid="login-button"]')
  
  // 2. Ajouter une dépense
  await page.click('[data-testid="add-expense"]')
  await page.fill('[data-testid="amount"]', '25.50')
  await page.fill('[data-testid="description"]', 'E2E Test')
  await page.click('[data-testid="save"]')
  
  // 3. Vérifier l'affichage
  await expect(page.locator('text=E2E Test')).toBeVisible()
  await expect(page.locator('text=€25.50')).toBeVisible()
})
```

## 📊 Exécution des tests

### Tests manuels
```bash
# Tests backend
cd bank-backend
php bin/phpunit

# Tests frontend
cd bank-frontend
npm run test

# Tests E2E
npm run test:e2e
```

### Tests automatiques (CI/CD)
- **À chaque push** → Tests unitaires et d'intégration
- **À chaque pull request** → Tests complets + review
- **À chaque release** → Tests E2E + déploiement

### Rapports de tests
- **Couverture de code** : Minimum 80% backend, 75% frontend
- **Rapports de sécurité** : Vulnérabilités détectées et corrigées
- **Performance** : Temps de réponse < 200ms pour les API

## 🛠️ Configuration rapide

### Cloner et lancer l'application

```bash
# 1. Cloner le repository
git clone https://github.com/katekate7/bank_classic.git
cd bank_classic

# 2. Lancer avec Docker
docker-compose up -d

# 3. Accéder à l'application
# Frontend: http://localhost:5173
# Backend: http://localhost:8000
# API Docs: http://localhost:8000/api/doc
```

### Configuration des secrets GitHub

Pour le CI/CD, configurer ces secrets dans GitHub :
```
DOCKER_USERNAME=katekate7
DOCKER_PASSWORD=your_docker_token
```

## 🆘 Dépannage

### Problèmes courants

#### Docker ne démarre pas
```bash
# Vérifier le statut
docker info

# Redémarrer Docker Desktop
# Windows/Mac: Redémarrer depuis l'interface
# Linux: sudo systemctl restart docker
```

#### Ports déjà utilisés
```bash
# Vérifier les ports utilisés
netstat -tulpn | grep :5173
netstat -tulpn | grep :8000
netstat -tulpn | grep :3306

# Arrêter les processus ou changer les ports dans docker-compose.yml
```

#### Base de données corrompue
```bash
# Supprimer les volumes Docker
docker-compose down -v
docker-compose up -d
```

### Support

- **Documentation** : [GitHub Repository](https://github.com/katekate7/bank_classic)
- **Issues** : Signaler les bugs via GitHub Issues
- **Contact** : @katekate7

## 📈 Monitoring et maintenance

### Surveillance continue
- **Health checks** : `/api/health` endpoint
- **Logs centralisés** : Docker logs + application logs
- **Métriques** : Performance, utilisation des ressources
- **Alertes** : Notifications en cas de problème

### Maintenance régulière
- **Mises à jour de sécurité** : Images Docker, dépendances
- **Sauvegarde de données** : Base de données automatisée
- **Nettoyage** : Images Docker inutilisées, logs anciens
- **Tests de récupération** : Procédures de disaster recovery

Cette infrastructure garantit une application **robuste**, **sécurisée** et **maintenue automatiquement** avec un minimum d'intervention manuelle.
