# Bank Application Setup Guide

## Environment Files Configuration

This project uses environment files to store sensitive configuration. These files are ignored by git for security.

### 1. Root Level Configuration

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
```

Edit `.env` with your specific values:
- Database credentials
- Docker registry credentials (for deployment)
- Server deployment details
- Notification email

### 2. Backend Configuration

```bash
cd bank-backend
cp .env.example .env
```

Configure your backend environment:
- Database URL
- App secret
- JWT configuration
- CORS settings

### 3. Frontend Configuration

```bash
cd bank-frontend
cp .env.example .env
```

Configure your frontend environment:
- API base URL
- Feature flags

## Quick Start

### Development with Docker

1. Copy all environment files:
```bash
cp .env.example .env
cp bank-backend/.env.example bank-backend/.env
cp bank-frontend/.env.example bank-frontend/.env
```

2. Start the development environment:
```bash
docker-compose up -d
```

3. Run database migrations:
```bash
docker-compose exec backend php bin/console doctrine:migrations:migrate
```

### Running Tests

Run all tests:
```bash
./run-tests.sh
```

Run specific test suites:
```bash
# Backend tests only
./run-tests.sh backend

# Frontend tests only
./run-tests.sh frontend
```

### Production Deployment

1. Configure production environment files
2. Build and deploy:
```bash
./deploy.sh
```

3. Update running application:
```bash
./update-app.sh
```

## CI/CD Pipeline

### GitHub Actions

The project includes automated CI/CD with GitHub Actions. The workflow:
1. Runs on push to main branch
2. Builds Docker images
3. Runs test suite
4. Deploys to production (on main branch)

### Jenkins (Alternative)

Jenkins configuration is available in `Jenkinsfile` and `Jenkinsfile.test` for:
- Continuous integration
- Test execution
- Deployment automation

## Security Notes

- Never commit `.env` files to git
- Use strong passwords and secrets
- Rotate credentials regularly
- Ensure your GitHub token has the `workflow` scope for CI/CD updates

## Documentation

- [Integration Tests](INTEGRATION_TESTS.md)
- [CI/CD Documentation](CI-CD-DOCUMENTATION.md)
- [Test Summary](test-summary.md)
