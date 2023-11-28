.DEFAULT_GOAL := help

.PHONY: build
build: ## Build the Docker containers
	docker compose build

.PHONY: start
start: ## Start the application
	docker compose up -d

.PHONY: stop
stop: ## Stop the application
	docker compose down

.PHONY: logs
logs: ## Tail the logs for the application
	docker compose logs -f

.PHONY: db-recreate
db-recreate: ## Recreate the database and apply migrations
	docker compose exec php bin/console d:d:d --no-interaction --force
	docker compose exec php bin/console d:d:c
	docker compose exec php bin/console d:m:m --no-interaction --quiet

.PHONY: db-dump
db-dump: ## Run GitHub Actions locally with act
	docker-compose exec database pg_dump -Uapp gptcorn -c > gptcorn_dump_`date +%d-%m-%Y"_"%H_%M_%S`.sql

.PHONY: act
act: ## Run GitHub Actions locally with act
	act --container-architecture linux/amd64 -P ubuntu-latest=ghcr.io/catthehacker/ubuntu:act-latest --secret-file .secrets

.PHONY: help
help: ## Display this help message
	@echo "Usage: make [target] ...\n"
	@echo "Available targets:"
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)
