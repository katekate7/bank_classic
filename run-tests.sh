#!/bin/bash

# Bank Application Test Runner
# This script runs all tests for the banking application

set -e

echo "🏦 Starting Bank Application Test Suite"
echo "======================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Parse command line arguments
BACKEND_ONLY=false
FRONTEND_ONLY=false
INTEGRATION_ONLY=false
COVERAGE=false
VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --backend-only)
            BACKEND_ONLY=true
            shift
            ;;
        --frontend-only)
            FRONTEND_ONLY=true
            shift
            ;;
        --integration-only)
            INTEGRATION_ONLY=true
            shift
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --backend-only      Run only backend tests"
            echo "  --frontend-only     Run only frontend tests"
            echo "  --integration-only  Run only integration tests"
            echo "  --coverage          Generate coverage reports"
            echo "  --verbose           Verbose output"
            echo "  --help              Show this help message"
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            echo "Use --help for usage information."
            exit 1
            ;;
    esac
done

# Start test environment
print_status "Setting up test environment..."

# Build test containers
if [[ "$FRONTEND_ONLY" != true ]]; then
    print_status "Building backend test environment..."
    docker-compose -f docker-compose.test.yml build backend-test
fi

if [[ "$BACKEND_ONLY" != true ]]; then
    print_status "Building frontend test environment..."
    docker-compose -f docker-compose.test.yml build frontend-test
fi

# Run Backend Tests
if [[ "$FRONTEND_ONLY" != true ]]; then
    print_status "Running Backend Tests..."
    echo "========================"
    
    # Run unit tests
    if [[ "$INTEGRATION_ONLY" != true ]]; then
        print_status "Running PHP Unit Tests..."
        if [[ "$COVERAGE" == true ]]; then
            docker-compose -f docker-compose.test.yml run --rm backend-test \
                vendor/bin/phpunit tests/Entity/ tests/Form/ tests/Repository/ --coverage-html var/coverage
        else
            docker-compose -f docker-compose.test.yml run --rm backend-test \
                vendor/bin/phpunit tests/Entity/ tests/Form/ tests/Repository/
        fi
        
        if [[ $? -eq 0 ]]; then
            print_success "Backend unit tests passed!"
        else
            print_error "Backend unit tests failed!"
            exit 1
        fi
    fi
    
    # Run controller tests
    if [[ "$INTEGRATION_ONLY" != true ]]; then
        print_status "Running Controller Tests..."
        docker-compose -f docker-compose.test.yml run --rm backend-test \
            vendor/bin/phpunit tests/Controller/
        
        if [[ $? -eq 0 ]]; then
            print_success "Controller tests passed!"
        else
            print_error "Controller tests failed!"
            exit 1
        fi
    fi
fi

# Run Frontend Tests
if [[ "$BACKEND_ONLY" != true && "$INTEGRATION_ONLY" != true ]]; then
    print_status "Running Frontend Tests..."
    echo "========================="
    
    # Install dependencies first
    print_status "Installing frontend dependencies..."
    docker-compose -f docker-compose.test.yml run --rm frontend-test npm ci
    
    # Run frontend unit tests
    print_status "Running React Component Tests..."
    if [[ "$COVERAGE" == true ]]; then
        docker-compose -f docker-compose.test.yml run --rm frontend-test npm run test:coverage
    else
        docker-compose -f docker-compose.test.yml run --rm frontend-test npm run test
    fi
    
    if [[ $? -eq 0 ]]; then
        print_success "Frontend tests passed!"
    else
        print_error "Frontend tests failed!"
        exit 1
    fi
fi

# Run Integration Tests
if [[ "$FRONTEND_ONLY" != true ]]; then
    print_status "Running Integration Tests..."
    echo "============================="
    
    # Start test database
    print_status "Starting test database..."
    docker-compose -f docker-compose.test.yml up -d test-db
    
    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 10
    
    # Run database integration tests
    print_status "Running Database Integration Tests..."
    docker-compose -f docker-compose.test.yml run --rm integration-test \
        vendor/bin/phpunit tests/Integration/
    
    if [[ $? -eq 0 ]]; then
        print_success "Integration tests passed!"
    else
        print_error "Integration tests failed!"
        docker-compose -f docker-compose.test.yml down
        exit 1
    fi
    
    # Run E2E tests
    print_status "Running End-to-End Tests..."
    docker-compose -f docker-compose.test.yml run --rm integration-test \
        vendor/bin/phpunit tests/E2E/
    
    if [[ $? -eq 0 ]]; then
        print_success "E2E tests passed!"
    else
        print_error "E2E tests failed!"
        docker-compose -f docker-compose.test.yml down
        exit 1
    fi
    
    # Clean up test environment
    print_status "Cleaning up test environment..."
    docker-compose -f docker-compose.test.yml down
fi

# Generate test report
print_status "Generating test report..."
echo ""
echo "🎉 Test Suite Summary"
echo "===================="

if [[ "$FRONTEND_ONLY" != true ]]; then
    print_success "✅ Backend Tests: PASSED"
fi

if [[ "$BACKEND_ONLY" != true && "$INTEGRATION_ONLY" != true ]]; then
    print_success "✅ Frontend Tests: PASSED"
fi

if [[ "$FRONTEND_ONLY" != true ]]; then
    print_success "✅ Integration Tests: PASSED"
    print_success "✅ E2E Tests: PASSED"
fi

echo ""
if [[ "$COVERAGE" == true ]]; then
    print_status "Coverage reports generated:"
    if [[ "$FRONTEND_ONLY" != true ]]; then
        echo "  - Backend: bank-backend/var/coverage/index.html"
    fi
    if [[ "$BACKEND_ONLY" != true && "$INTEGRATION_ONLY" != true ]]; then
        echo "  - Frontend: bank-frontend/coverage/index.html"
    fi
fi

print_success "🎉 All tests completed successfully!"
echo ""
print_status "Your banking application is ready for deployment! 🚀"
