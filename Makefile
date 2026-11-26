COMPOSE := docker compose $(if $(wildcard .env.local),--env-file .env.local,)

.PHONY: help setup build up down restart deploy migrate fixtures cache assets backup logs shell check create-admin

help:
	@grep -E '^[a-zA-Z_-]+:' Makefile | cut -d: -f1

setup:
	@if [ ! -f .env.local ]; then cp .env.local.example .env.local; echo ".env.local créé : renseignez les secrets avant de déployer."; fi

build:
	$(COMPOSE) build --pull

up:
	$(COMPOSE) up -d --remove-orphans

down:
	$(COMPOSE) down --remove-orphans

restart:
	$(COMPOSE) restart

deploy: build up migrate cache

migrate:
	$(COMPOSE) exec -T php php bin/console doctrine:migrations:migrate --no-interaction --env=prod

fixtures:
	@echo "Les fixtures écrasent la base : à réserver à un environnement de démonstration."
	$(COMPOSE) exec -T php php bin/console doctrine:fixtures:load --no-interaction

cache:
	$(COMPOSE) exec -T php php bin/console cache:clear --env=prod --no-warmup
	$(COMPOSE) exec -T php php bin/console cache:warmup --env=prod

assets:
	npm install --no-audit --no-fund
	npm run build

backup:
	$(COMPOSE) exec -T php php bin/console app:backup:run --env=prod

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec php sh

check:
	$(COMPOSE) exec -T php php bin/console lint:container --env=prod
	$(COMPOSE) exec -T php php bin/console doctrine:schema:validate --env=prod

create-admin:
	@test -n "$(EMAIL)" || (echo "Usage: make create-admin EMAIL=admin@exemple.org"; exit 1)
	$(COMPOSE) exec -T php php bin/console app:user:create --env=prod "$(EMAIL)"
