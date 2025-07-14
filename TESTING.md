# Bank Application - Testing Guide

This comprehensive testing suite ensures the reliability and quality of the Bank application through multiple layers of testing.

## 🧪 Test Types Implemented

### 1. Backend Tests (PHP/Symfony)

#### Unit Tests
- **Entity Tests**: Test business logic and entity relationships
- **Form Tests**: Validate form handling and data binding
- **Repository Tests**: Test database queries and data access

#### Integration Tests
- **Controller Tests**: Test HTTP endpoints and request/response handling
- **API Tests**: Test REST API functionality
- **Database Integration**: Test ORM relationships and transactions

#### End-to-End Tests
- **Complete Workflow Tests**: Test full user scenarios
- **Multi-user Isolation**: Ensure data security between users
- **Error Handling**: Test various error scenarios
- **Data Consistency**: Verify data integrity across interfaces

### 2. Frontend Tests (React/Vitest)

#### Component Tests
- **ExpenseCard**: Test expense display and interactions
- **AddExpenseForm**: Test form functionality and validation
- **Dashboard**: Test expense management features

#### Integration Tests
- **API Integration**: Test frontend-backend communication
- **User Interactions**: Test complete user workflows
- **Error Handling**: Test error states and recovery

### 3. System Integration Tests

#### Full Stack Tests
- **Frontend ↔ Backend**: Test complete request/response cycles
- **Database Consistency**: Verify data persistence across layers
- **Authentication Flow**: Test user authentication end-to-end

## 🚀 Running Tests

### Quick Start

```bash
# Run all tests
./run-tests.sh

# Run with coverage
./run-tests.sh --coverage

# Run specific test suites
./run-tests.sh --backend-only
./run-tests.sh --frontend-only
./run-tests.sh --integration-only
```

### Manual Test Execution

#### Backend Tests
```bash
cd bank-backend

# Unit tests
vendor/bin/phpunit tests/Entity/
vendor/bin/phpunit tests/Form/
vendor/bin/phpunit tests/Repository/

# Controller tests
vendor/bin/phpunit tests/Controller/

# Integration tests
vendor/bin/phpunit tests/Integration/

# E2E tests
vendor/bin/phpunit tests/E2E/

# All tests with coverage
vendor/bin/phpunit --coverage-html var/coverage
```

#### Frontend Tests
```bash
cd bank-frontend

# Install dependencies
npm ci

# Run tests
npm run test

# Run tests with coverage
npm run test:coverage

# Run tests with UI
npm run test:ui
```

### Docker Test Environment

```bash
# Build test environment
docker-compose -f docker-compose.test.yml build

# Run backend tests
docker-compose -f docker-compose.test.yml run --rm backend-test vendor/bin/phpunit

# Run frontend tests
docker-compose -f docker-compose.test.yml run --rm frontend-test npm run test

# Run integration tests
docker-compose -f docker-compose.test.yml up -d test-db
docker-compose -f docker-compose.test.yml run --rm integration-test vendor/bin/phpunit tests/Integration/
docker-compose -f docker-compose.test.yml down
```

## 📊 Test Coverage

### Backend Coverage Goals
- **Entities**: 100% (all business logic tested)
- **Controllers**: 95% (all endpoints tested)
- **Forms**: 90% (form validation tested)
- **Repositories**: 90% (database queries tested)

### Frontend Coverage Goals
- **Components**: 90% (all UI components tested)
- **Pages**: 85% (all page functionality tested)
- **API Calls**: 95% (all API interactions tested)

### Current Coverage
Generate coverage reports to see current status:

```bash
# Backend coverage
./run-tests.sh --backend-only --coverage
# View: bank-backend/var/coverage/index.html

# Frontend coverage
./run-tests.sh --frontend-only --coverage
# View: bank-frontend/coverage/index.html
```

## 🔧 Test Configuration

### Environment Files

#### `.env.test` (Backend)
```bash
APP_ENV=test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
MAILER_DSN=null://null
```

#### `vitest.config.js` (Frontend)
```javascript
export default defineConfig({
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.js'],
  }
})
```

### Test Database
- **Development**: MySQL database
- **Testing**: SQLite in-memory database for speed
- **Integration**: Dedicated MySQL test instance

## 🏗️ Test Architecture

### Testing Pyramid

```
    🔺 E2E Tests (Few)
     ├─ Complete user workflows
     └─ Cross-system integration
     
   🔶 Integration Tests (Some)
    ├─ API endpoint testing
    ├─ Database integration
    └─ Component integration
    
  🔷 Unit Tests (Many)
   ├─ Entity logic
   ├─ Form validation
   ├─ Component behavior
   └─ Business rules
```

### Test Organization

```
bank-backend/tests/
├── Entity/           # Unit tests for entities
├── Form/            # Unit tests for forms  
├── Repository/      # Unit tests for repositories
├── Controller/      # Integration tests for controllers
├── Integration/     # Database integration tests
└── E2E/            # End-to-end tests

bank-frontend/src/test/
├── components/      # Component unit tests
├── pages/          # Page integration tests
├── api/            # API integration tests
└── setup.js        # Test configuration
```

## 🎯 Test Scenarios Covered

### User Management
- ✅ User registration and authentication
- ✅ Password hashing and validation
- ✅ User session management
- ✅ Access control and authorization

### Expense Management
- ✅ Create new expenses
- ✅ Read expense lists and details
- ✅ Update existing expenses
- ✅ Delete expenses
- ✅ Category assignment
- ✅ Date and amount validation

### Data Security
- ✅ User data isolation
- ✅ Input validation and sanitization
- ✅ SQL injection prevention
- ✅ XSS protection
- ✅ CSRF protection

### Error Handling
- ✅ Invalid input handling
- ✅ Database connection errors
- ✅ API communication failures
- ✅ Authentication failures
- ✅ Authorization failures

### Performance
- ✅ Database query optimization
- ✅ N+1 query prevention
- ✅ Frontend rendering performance
- ✅ API response times

## 🔄 Continuous Integration

### Jenkins Pipeline
The `Jenkinsfile.test` defines a complete CI/CD pipeline:

1. **Build Stage**: Build Docker images
2. **Test Stage**: Run all test suites in parallel
3. **Integration Stage**: Run integration tests
4. **E2E Stage**: Run end-to-end tests
5. **Security Stage**: Run security scans
6. **Quality Stage**: Run code quality checks
7. **Performance Stage**: Run performance tests
8. **Deploy Stage**: Deploy to staging environment

### Automated Testing
- Tests run on every commit
- Pull requests require passing tests
- Coverage reports generated automatically
- Test results published to build dashboard

## 🐛 Debugging Failed Tests

### Backend Test Debugging
```bash
# Run specific test with verbose output
vendor/bin/phpunit tests/Entity/ExpenseTest.php --verbose

# Debug with Xdebug
XDEBUG_CONFIG="idekey=PHPSTORM" vendor/bin/phpunit tests/Entity/ExpenseTest.php

# Check logs
tail -f var/log/test.log
```

### Frontend Test Debugging
```bash
# Run specific test
npm test -- ExpenseCard.test.jsx

# Run with debugging
npm test -- --inspect-brk ExpenseCard.test.jsx

# Check browser console
npm run test:ui
```

### Common Issues and Solutions

#### Database Connection Issues
```bash
# Check database connection
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

#### Frontend Module Issues
```bash
# Clear node modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

#### Docker Issues
```bash
# Clean up Docker environment
docker system prune -f
docker-compose -f docker-compose.test.yml down --volumes
```

## 📈 Test Metrics and Reporting

### Coverage Reports
- **Backend**: HTML reports in `bank-backend/var/coverage/`
- **Frontend**: HTML reports in `bank-frontend/coverage/`

### Test Reports
- **JUnit XML**: Compatible with CI/CD systems
- **HTML Reports**: Human-readable test results
- **Performance Metrics**: Response time measurements

### Quality Gates
- Minimum 80% code coverage required
- All tests must pass before deployment
- No critical security vulnerabilities allowed
- Performance benchmarks must be met

## 🎓 Best Practices

### Writing Tests
1. **Follow AAA Pattern**: Arrange, Act, Assert
2. **Use Descriptive Names**: Test names should explain what is being tested
3. **Test One Thing**: Each test should verify one specific behavior
4. **Use Test Doubles**: Mock external dependencies
5. **Clean Up**: Always clean up test data

### Test Data Management
1. **Use Factories**: Create test data consistently
2. **Isolate Tests**: Each test should be independent
3. **Clean State**: Start each test with a clean state
4. **Realistic Data**: Use realistic test data

### Performance
1. **Fast Tests**: Keep unit tests fast (< 1s each)
2. **Parallel Execution**: Run tests in parallel when possible
3. **Optimize Setup**: Minimize test setup time
4. **Smart Cleanup**: Only clean up what's necessary

## 🚨 Troubleshooting

### Test Environment Setup Issues

#### Backend Setup
```bash
# Install PHP dependencies
composer install --dev

# Set up test database
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test

# Load test fixtures
php bin/console doctrine:fixtures:load --env=test --no-interaction
```

#### Frontend Setup
```bash
# Install Node dependencies
npm ci

# Verify Vitest configuration
npx vitest --reporter=verbose --run
```

### Common Test Failures

#### "Database not found" Error
```bash
# Create test database
php bin/console doctrine:database:create --env=test
```

#### "Module not found" Error (Frontend)
```bash
# Clear npm cache and reinstall
npm cache clean --force
rm -rf node_modules
npm install
```

#### "Permission denied" Error
```bash
# Fix file permissions
chmod +x run-tests.sh
sudo chown -R $USER:$USER .
```

## 📚 Additional Resources

- [Symfony Testing Documentation](https://symfony.com/doc/current/testing.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Vitest Documentation](https://vitest.dev/)
- [React Testing Library](https://testing-library.com/docs/react-testing-library/intro/)

---

**Remember**: Tests are your safety net. They give you confidence to refactor, add features, and deploy your application with certainty that everything works as expected! 🛡️
