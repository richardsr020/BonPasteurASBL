#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")/.."

if [ ! -f .env.local ]; then
    echo "Fichier .env.local manquant : copiez .env.local.example puis renseignez les secrets."
    exit 1
fi

COMPOSE="docker compose --env-file .env.local"

if grep -q 'REPLACE_' .env.local; then
    echo "Attention : des valeurs REPLACE_ restent dans .env.local."
fi

$COMPOSE build --pull
$COMPOSE up -d --remove-orphans
$COMPOSE exec -T php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
$COMPOSE exec -T php php bin/console cache:clear --env=prod --no-warmup
$COMPOSE exec -T php php bin/console cache:warmup --env=prod
$COMPOSE ps
