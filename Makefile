# KMS TV — one entry point per task.
#
# Make is present in every environment we target, and a single entry point means
# a contributor does not have to know which workspace a command belongs to.

SHELL := /bin/bash
COMPOSE := docker compose -f infrastructure/docker/compose.yaml
API := services/core-api

.DEFAULT_GOAL := help
.PHONY: help up down reset seed migrate test test-unit test-feature lint format analyse boundaries spec check

help: ## List targets
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'

up: ## Start the local stack (PostgreSQL, Redis, PHP-FPM, Nginx, worker, Mailpit)
	@test -f $(API)/.env || cp $(API)/.env.example $(API)/.env
	$(COMPOSE) up -d --build
	$(COMPOSE) exec -T core-api php artisan migrate --force
	@echo "API on http://localhost:8080 — mail on http://localhost:8025"

down: ## Stop the stack, preserving data
	$(COMPOSE) down

reset: ## Stop the stack and destroy all local data
	$(COMPOSE) down -v

migrate: ## Run migrations
	$(COMPOSE) exec -T core-api php artisan migrate --force

seed: ## Load the seed dataset
	$(COMPOSE) exec -T core-api php artisan db:seed --force

test: ## Everything CI runs
	cd $(API) && php artisan test

test-unit: ## Unit tests only
	cd $(API) && php artisan test --testsuite=Unit

test-feature: ## Integration tests (real PostgreSQL and Redis)
	cd $(API) && php artisan test --testsuite=Feature

lint: ## Formatting check
	cd $(API) && vendor/bin/pint --test

format: ## Apply formatting
	cd $(API) && vendor/bin/pint

analyse: ## Static analysis
	cd $(API) && vendor/bin/phpstan analyse --memory-limit=1G

boundaries: ## Enforce module boundaries (ADR-0001)
	php tools/check-module-boundaries.php

spec: ## Validate the OpenAPI contract against the implementation
	cd $(API) && php artisan test --filter=OpenApiConformance

check: boundaries lint analyse test ## Everything, in the order CI runs it
