# Playbook de déploiement — ASBL Bon Pasteur RDC

## 1. Pré-requis

- Docker Engine 24+ et Docker Compose v2 sur le serveur.
- Un nom de domaine pointant vers le serveur.
- Un reverse proxy TLS ou un certificat terminé en amont : le nginx du projet écoute sur le port HTTP interne `HTTP_PORT`.
- Accès SMTP pour l'envoi des messages et un serveur MySQL externe si la base Docker n'est pas utilisée.

## 2. Configuration

```sh
cp .env.local.example .env.local
openssl rand -hex 32
```

Renseigner `.env.local` :

- `APP_SECRET` : valeur générée par `openssl rand -hex 32`.
- `DATABASE_URL`, `MYSQL_ROOT_PASSWORD`, `MYSQL_USER`, `MYSQL_PASSWORD` : identifiants cohérents entre eux.
- `MAILER_DSN` : DSN SMTP de production.
- `DEFAULT_URI` : URL publique complète, utilisée pour générer les liens absolus.
- `TRANSLATION_API_KEY` : clé DeepL ; laisser vide pour fallback français.
- `HTTP_PORT` : port d'exposition du nginx.

Ne jamais committer `.env.local` : il est déjà ignoré par Git.

## 3. Premier déploiement

```sh
make build
make up
make migrate
make cache
make create-admin EMAIL=admin@exemple.org
```

Le mot de passe administrateur est demandé de manière masquée. Le compte est créé avec `ROLE_ADMIN`.

Pour un environnement de démonstration uniquement :

```sh
make fixtures
```

Les fixtures écrasent la base et créent des comptes avec mots de passe connus : ne pas les exécuter en production.

## 4. Déploiement courant

```sh
make backup
git pull
make deploy
make check
```

`make deploy` enchaîne : build, redémarrage des conteneurs, migrations Doctrine et warmup du cache.

Équivalent direct :

```sh
./scripts/deploy.sh
```

Le script refusera de démarrer si `.env.local` est absent et affichera un avertissement si des valeurs `REPLACE_` subsistent.

## 5. Exploitation

| Commande | Rôle |
| --- | --- |
| `make logs` | Suivre les logs de tous les services |
| `make shell` | Ouvrir un shell dans le conteneur PHP |
| `make backup` | Créer une sauvegarde compressée |
| `make migrate` | Appliquer les migrations |
| `make cache` | Vider et réchauffer le cache Symfony |
| `make down` | Arrêter la stack |

Sauvegardes :

- Stockées dans `var/backups/<horodatage>/`.
- `database.sql.gz` : base de données.
- `archives.tar.gz` et `uploads.tar.gz` : fichiers privés et publics.
- Rétention : 7 sauvegardes quotidiennes, 4 hebdomadaires et 12 mensuelles.
- Une tâche cron dans le conteneur `cron` exécute `app:backup:run` chaque jour à 02:00.

## 6. Vérification post-déploiement

```sh
curl -I http://127.0.0.1:${HTTP_PORT:-8080}/fr
docker compose --env-file .env.local ps
make check
```

À vérifier dans l'application :

- `/fr` et `/en` répondent.
- `/admin` redirige vers `/login`.
- Le compte administrateur créé peut se connecter.
- Les pages statiques, documents publics et médias s'affichent.
- Une sauvegarde apparaît dans `/admin/backups`.

## 7. Dépannage

- `500` après un changement de configuration : `make cache`.
- Migration non appliquée : `make migrate`.
- Fichiers uploadés absents : vérifier les volumes `uploads_public` et `archives_private`.
- Traduction anglaise non active : vérifier `TRANSLATION_API_KEY` ; sans clé, le site reste en français.
- Logs applicatifs : `make logs`.
