.PHONY: help install dev test lint docker-up docker-down

# Help target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# Install dependencies
install: ## Install PHP and Node dependencies
	composer install
	npm install

# Development
dev: ## Start development server
	php artisan serve --host=0.0.0.0 --port=8000

# Testing
test: ## Run tests
	php artisan test

test-coverage: ## Run tests with coverage
	php artisan test --coverage --min=70

# Code Quality
# NOTA: ESLint/Prettier para Vue se agregan en la Fase 4.6 (sistema de diseño)
lint: ## Run linters
	vendor/bin/pint --test

format: ## Format code
	vendor/bin/pint

# Docker
docker-up: ## Start Docker containers
	docker-compose up -d

docker-down: ## Stop Docker containers
	docker-compose down

docker-build: ## Build Docker images
	docker-compose build

# Database
migrate: ## Run migrations
	php artisan migrate

seed: ## Run database seeder
	php artisan db:seed

fresh: ## Fresh migration with seed
	php artisan migrate:fresh --seed

# Cache
cache-clear: ## Clear all caches
	php artisan optimize:clear
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear

# Assets
build: ## Build frontend assets
	npm run build

watch: ## Watch for asset changes
	npm run dev
