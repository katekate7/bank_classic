# GitHub CI/CD Workflow Guide

## 🔄 How the CI Process Works on GitHub

### Overview
The GitHub Actions workflow in `.github/workflows/ci-cd.yml` provides a complete CI/CD pipeline that:

1. **Tests** your application using MySQL service
2. **Builds** Docker images for production
3. **Deploys** to your production server

### 🧪 Test Job (Native PHP/Node.js)

#### Services Used
- **MySQL 8.0** as a service container
- **PHP 8.2** with required extensions
- **Node.js 20** for frontend testing

#### Database Configuration
```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: bank_test
      MYSQL_USER: bank_user
      MYSQL_PASSWORD: bank_password
    ports:
      - 3306:3306
    options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
```

#### Test Steps
1. **Checkout code** from the repository
2. **Setup PHP 8.2** with extensions: `mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, json, pdo, sqlite3`
3. **Install Composer dependencies** in `./bank-backend/`
4. **Setup environment**:
   - Copy `.env.test` to `.env`
   - Wait for MySQL to be ready
5. **Database setup**:
   - Create test database
   - Run migrations
   - Load fixtures
6. **Run Symfony tests** with PHPUnit
7. **Setup Node.js 20** with npm cache
8. **Install frontend dependencies** with `npm ci`
9. **Run frontend tests** with Vitest

### 🏗️ Build Job (Docker-based)

**Triggered only on `main` branch pushes**

1. **Build Docker images** for backend and frontend
2. **Push to Docker registry** with tags:
   - `latest`
   - `{git-sha}`
3. **Use BuildKit cache** for faster builds

### 🚀 Deploy Job

**Triggered only on `main` branch pushes**

1. **SSH to production server**
2. **Pull latest images**
3. **Rolling update** with zero downtime
4. **Health checks**
5. **Cleanup old images**

## 🔧 Configuration Files

### Backend Environment (`.env.test`)
```bash
# Test Database Configuration
# Use MySQL for GitHub Actions CI
DATABASE_URL="mysql://root:root@127.0.0.1:3306/bank_test"

# Other test configurations...
APP_ENV=test
APP_SECRET='$ecretf0rt3st'
MAILER_DSN=null://null
```

### PHPUnit Configuration (`phpunit.xml.dist`)
- Bootstrap: `tests/bootstrap.php`
- Test directory: `tests/`
- Environment: `test`
- Symfony integration enabled

### Frontend Testing (`package.json`)
```json
{
  "scripts": {
    "test": "vitest",
    "test:coverage": "vitest --coverage"
  }
}
```

## 🚦 Trigger Conditions

### When Tests Run
- **Every push** to `main` or `develop` branches
- **Every pull request** to `main` branch

### When Build & Deploy Run
- **Only on push** to `main` branch
- **After successful tests**

## 🔐 Required Secrets

Set these in your GitHub repository settings:

### Docker Registry
- `DOCKER_USERNAME`: Your Docker Hub username
- `DOCKER_PASSWORD`: Your Docker Hub token

### Deployment Server
- `DEPLOY_HOST`: Production server IP/hostname
- `DEPLOY_USER`: SSH username
- `DEPLOY_KEY`: SSH private key

## 🐛 Troubleshooting

### Common Issues

1. **MySQL Connection Failed**
   - The workflow waits for MySQL to be ready
   - Health checks ensure MySQL is available before tests

2. **Composer Dependencies**
   - Uses `--no-progress --prefer-dist --optimize-autoloader` for speed
   - All dev dependencies are installed for testing

3. **Database Setup**
   - Creates database if it doesn't exist
   - Runs migrations automatically
   - Loads test fixtures

4. **Frontend Tests**
   - Uses `npm ci` for faster, reliable installs
   - Vitest runs in CI mode automatically

### Debugging Steps

1. **Check workflow logs** in GitHub Actions tab
2. **Verify database connection** in test output
3. **Check Composer install** for dependency issues
4. **Review test results** for specific failures

## ✅ Success Indicators

- ✅ MySQL service starts successfully
- ✅ PHP and Node.js setup complete
- ✅ Dependencies installed
- ✅ Database created and migrated
- ✅ All backend tests pass
- ✅ All frontend tests pass
- ✅ Docker images build successfully
- ✅ Deployment completes without errors

## 📊 Artifacts

The workflow uploads test artifacts:
- **Backend logs**: `bank-backend/var/log/`
- **Frontend coverage**: `bank-frontend/coverage/`

These are available for download from the GitHub Actions interface.
