# SPÉCIFICATIONS TECHNIQUES ET ARCHITECTURE DU PROJET : PLATEFORME WEB & GED — ASBL BON PASTEUR RDC

> **Destinataire :** Agent IA / Développeur Full-Stack Senior  
> **Objectif :** Conception, architecture et développement complet de la plateforme numérique de l'ASBL BON PASTEUR (RDC - Province du Lualaba), combinant un **site vitrine institutionnel public** et un **système interne de gestion documentaire et d'archivage numérique (GED)**, administrables via un back-office unifié.

---

## 1. STACK TECHNIQUE IMPOSÉE & ENVIRONNEMENT D'EXÉCUTION

L'architecture doit garantir une parité stricte entre l'environnement de développement local et le serveur de production (VPS Linux) sans conflits de dépendances.

### 1.1. Technologies Core
* **Backend Framework :** **Symfony 7.x LTS** (PHP 8.3+ avec typage strict `declare(strict_types=1);`).
* **Base de données :** **MySQL 8.0+** (moteur `InnoDB`, encodage `utf8mb4_unicode_ci`).
* **ORM :** **Doctrine ORM** avec migrations versionnées (`DoctrineMigrationsBundle`).
* **Moteur de rendu Front-End :** **Twig** (Server-Side Rendering) pour des temps de chargement minimaux adaptés aux connexions internet à faible bande passante.
* **Styling & UI :** **Tailwind CSS** (compilé et minifié) + architecture responsive mobile-first.
* **Interactivité Front-End :** **JavaScript Vanilla (ES6+ Modules)** couplé à **Symfony UX (Stimulus)** et des endpoints **JSON Symfony (`JsonResponse`)** pour les interactions dynamiques (filtres d'archives, modales de prévisualisation, carrousels, formulaires).
* **Gestion des Assets :** **Symfony AssetMapper** (ou Webpack Encore si compilation Tailwind requise) — pas de SPA lourde (React/Vue/Angular exclus) afin de préserver la légèreté réseau.
* **Serveur Web & Reverse Proxy :** **Nginx** (gestion SSL/HTTPS, compression Gzip/Brotli, cache statique, streaming vidéo HTTP Range Requests, contrôle des tailles d'upload).

### 1.2. Infrastructure Docker (Dev & Prod)
Le projet doit être entièrement conteneurisé via **Docker** et **Docker Compose** avec séparation stricte entre le code applicatif (éphémère/immuable en prod) et les données persistantes.

**Services requis dans `docker-compose.yml` :**
1. `php` : Image basée sur `php:8.3-fpm-alpine` incluant les extensions obligatoires : `pdo_mysql`, `intl`, `zip`, `gd` / `imagick` (traitement d'images/miniatures), `opcache`, `fileinfo` (validation stricte des types MIME), `mbstring`, `xml`. Inclure `composer` et `symfony-cli` en dev.
2. `nginx` : Image `nginx:alpine` configurée avec `client_max_body_size 250M;` (pour les vidéos/rapports lourds), headers de sécurité (`X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`) et cache agressif sur `/assets/` et `/build/`.
3. `database` : Image `mysql:8.0` avec volume persistant `mysql_data`.
4. `mailer` (Dev uniquement) : `axllent/mailpit` pour tester le formulaire de contact et notifications.

**Volumes persistants obligatoires (Production) :**
* `mysql_data:/var/lib/mysql`
* `uploads_public:/var/www/html/public/uploads` (médias publics du site vitrine)
* `archives_private:/var/www/html/var/storage/archives` (**HORS de `public/`** — inaccessible directement par URL HTTP)
* `backups_storage:/var/www/html/var/backups` (dumps SQL + archives compressées)

---

## 2. ARCHITECTURE LOGICIELLE & CONVENTIONS SYMFONY

### 2.1. Structure des répertoires recommandée
```text
src/
├── Controller/
│   ├── Public/          # Vitrine publique (Home, About, Projects, News, Partners, Contact)
│   ├── Admin/           # Back-office CRUD (Contenus vitrine, Utilisateurs, Stats)
│   └── Ged/             # Système d'archivage numérique (Explorateur, Upload, Preview, Export)
├── Entity/              # Entités Doctrine (User, Project, ArchiveYear, ArchiveFolder, ArchiveDocument, etc.)
├── Repository/          # Requêtes DQL/QueryBuilder optimisées (recherche multicritère, pagination)
├── Form/                # Symfony FormTypes avec validation stricte
├── Security/            # Voters Symfony (ArchiveVoter, ContentVoter) & Authenticators
├── Service/
│   ├── FileStorage/     # Gestion sécurisée des uploads, hash SHA-256, détection MIME, stockage privé/public
│   ├── Preview/         # Génération d'aperçus sécurisés et streaming vidéo/PDF
│   ├── Export/          # Génération de rapports/index d'archives en PDF (Dompdf ou Gotenberg/Snappy)
│   └── Backup/          # Service de sauvegarde automatisée BDD + fichiers
└── EventSubscriber/     # AuditTrailSubscriber (traçabilité automatique des créations/modifications)
```

### 2.2. Sécurité & Contrôle d'Accès (RBAC)
* **Authentification :** `form_login` sécurisé sur `/login` avec protection CSRF, hachage des mots de passe via `auto` (Bcrypt/Argon2id), et limitation des tentatives de connexion (`login_throttling`).
* **Hiérarchie des Rôles (`security.yaml`) :**
  * `ROLE_COMM` (**Responsable de communication**) :
    * CRUD complet sur les contenus du site vitrine (Pages, Actualités, Projets publics, Témoignages, Partenaires, Médias publics).
    * Consultation, ajout, classement et téléversement dans les espaces d'archives autorisés.
    * Promotion d'un média d'archive interne vers la galerie publique du site (copie contrôlée vers `uploads_public`).
    * *Interdiction* de supprimer définitivement des archives critiques ou de gérer les utilisateurs.
  * `ROLE_ADMIN` (**Administrateur**) :
    * Hérite de `ROLE_COMM`.
    * Accès total à toutes les années, projets et dossiers d'archives.
    * Gestion des utilisateurs, des rôles et des permissions granulaires par dossier/projet.
    * Création/suppression d'années d'archives et gestion de la corbeille / restauration.
    * Déclenchement et téléchargement des sauvegardes système.
* **Voters :** Implémenter un `ArchiveVoter` vérifiant systématiquement les droits (`VIEW`, `UPLOAD`, `EDIT`, `DELETE`, `EXPORT`) avant toute action sur un dossier ou document.

---

## 3. MODÈLE DE DONNÉES (SCHÉMA MYSQL / DOCTRINE)

Toutes les entités critiques doivent implémenter un `TimestampableTrait` (`createdAt`, `updatedAt`) et un `BlameableTrait` (`createdBy`, `updatedBy` liés à `User`).

### 3.1. Entités du Site Vitrine (Partie Publique)
1. **`User`** : `id`, `email` (unique), `password`, `fullName`, `roles` (JSON), `isActive` (bool), `lastLoginAt`.
2. **`OrganizationSetting`** (Singleton/Clé-Valeur) :
   * Identité visuelle (logo, couleurs), présentation synthétique, mission, vision, valeurs, historique, objectifs institutionnels, plan stratégique `2024-2028`.
   * Chiffres clés dynamiques (JSON ou table dédiée : bénéficiaires touchés, zones d'intervention, projets réalisés, taux d'impact).
   * Coordonnées (adresse physique Lualaba, email institutionnel, téléphones, coordonnées GPS/carte, liens réseaux sociaux).
3. **`HeroSlide`** : `id`, `title`, `subtitle`, `imagePath`, `ctaLabel`, `ctaUrl`, `displayOrder`, `isActive` (pour le carrousel d'accueil défilant toutes les 30s).
4. **`InterventionDomain`** : `id`, `title`, `slug`, `summary`, `description`, `iconOrImage`, `displayOrder` (Protection enfant/femme, Éducation informelle, Formation professionnelle, Agriculture durable, Microcrédit).
5. **`Project`** (Vitrine publique) :
   * `id`, `title`, `slug`, `context`, `objectives`, `geographicZone`, `startDate`, `endDate`, `status` (enum: `ONGOING`, `COMPLETED`).
   * `targetGroups`, `mainActivities` (text/HTML sécurisé), `resultsObtained`, `showDonorPublicly` (bool).
   * Relations : `ManyToMany` avec `Partner`, `OneToMany` avec `ProjectMedia` (photos/vidéos), `OneToMany` avec `Testimonial`, `OneToMany` avec `PublicDocument`.
6. **`Article`** (Actualités, Activités, Communiqués, Recrutements/Offres) :
   * `id`, `title`, `slug`, `category` (enum: `NEWS`, `FIELD_ACTIVITY`, `ANNOUNCEMENT`, `RECRUITMENT`), `content`, `coverImage`, `publishedAt`, `isPublished` (bool), `attachmentPath` (pour les offres/communiqués PDF).
7. **`Testimonial`** (« Une photo, une histoire ») :
   * `id`, `beneficiaryName` (ou pseudonyme de protection), `roleOrContext`, `storyText`, `photoPath`, `videoUrl` (optionnel), `project` (nullable), `isFeaturedOnHome` (bool).
8. **`Partner`** :
   * `id`, `name`, `type` (enum: `TECHNICAL`, `FINANCIAL`, `DONOR`), `logoPath`, `websiteUrl`, `description`, `isVisibleOnHome` (bool).
9. **`Award`** (Prix déjà reçus) :
   * `id`, `title`, `issuer`, `year`, `description`, `imagePath`.
10. **`ContactMessage`** :
    * `id`, `senderName`, `senderEmail`, `subject`, `message`, `isRead` (bool), `createdAt`.
11. **`PageViewStat`** (Statistiques légères internes) :
    * `id`, `route`, `urlPath`, `visitedDate` (DATE), `hitsCount` (INT) — agrégation journalière légère sans cookies tiers lourds pour respecter la bande passante.

### 3.2. Entités du Système d'Archivage Numérique (GED)
1. **`ArchiveYear`** :
   * `id`, `year` (INT, unique, ex: `2024`, `2025`, `2026`), `description`, `isLocked` (bool).
2. **`ArchiveProject`** :
   * `id`, `archiveYear` (`ManyToOne` -> `ArchiveYear`), `title`, `code`, `description`, `linkedPublicProject` (`OneToOne` nullable -> `Project`).
   * *Note d'initialisation :* À la création d'un `ArchiveProject`, générer automatiquement les **7 dossiers racines standardisés** exigés par le cahier des charges :
     1. `01 - Identification du projet`
     2. `02 - Planification`
     3. `03 - Activités réalisées`
     4. `04 - Résultats et impact`
     5. `05 - Témoignages et médias`
     6. `06 - Partenaires et donateurs`
     7. `07 - Rapport complet et clôture`
3. **`ArchiveFolder`** (Arborescence récursive) :
   * `id`, `archiveProject` (`ManyToOne`), `parent` (`ManyToOne` self-referencing, nullable), `name`, `slug`, `isSystemFolder` (bool, protège les 7 dossiers racines), `restrictedToAdmin` (bool, pour la confidentialité).
4. **`ArchiveDocument`** :
   * `id`, `folder` (`ManyToOne` -> `ArchiveFolder`), `title`, `originalFilename`, `storedFilename` (UUID v4 + extension sécurisée), `storagePath` (chemin relatif dans `archives_private`), `mimeType`, `fileExtension`, `fileSize` (BIGINT), `checksumSha256` (VARCHAR 64).
   * **Traçabilité & Conservation :** `version` (INT, défaut `1`), `createdAt`, `updatedAt`, `uploadedBy` (`ManyToOne` -> `User`), `updatedBy` (`ManyToOne` -> `User`).
   * **Protection contre suppression accidentelle (Soft Delete) :** `deletedAt` (DATETIME nullable), `deletedBy` (`ManyToOne` -> `User` nullable). Seul `ROLE_ADMIN` peut purger définitivement un fichier de la corbeille.
5. **`ArchiveAuditLog`** :
   * `id`, `user` (`ManyToOne`), `action` (enum: `UPLOAD`, `UPDATE_FILE`, `VIEW_PREVIEW`, `DOWNLOAD`, `MOVE`, `SOFT_DELETE`, `RESTORE`, `PURGE`, `EXPORT_PDF`), `entityType`, `entityId`, `details` (JSON), `ipAddress`, `createdAt`.

---

## 4. SPÉCIFICATIONS FONCTIONNELLES DÉTAILLÉES

### 4.1. Gestion des Fichiers, Sécurité Upload & Prévisualisation
* **Formats autorisés :**
  * Documents : `.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx`, `.ppt`, `.pptx`
  * Images : `.jpg`, `.jpeg`, `.png`, `.webp`
  * Vidéos : `.mp4`, `.webm`
* **Validation stricte à l'upload :**
  1. Vérifier le type MIME réel via `Symfony\Component\Mime\MimeTypes` (`finfo`), **ne jamais** se fier uniquement à l'extension client.
  2. Renommer physiquement chaque fichier avec un UUID (`uuid_v4.ext`) pour empêcher toute traversée de répertoire (`../`) ou exécution de script malveillant.
  3. Désactiver l'exécution PHP dans Nginx sur tous les dossiers d'upload (`location ~* ^/uploads/.*\.php$ { return 403; }`).
* **Prévisualisation sans téléchargement (`/admin/ged/document/{id}/preview`) :**
  * **Sécurité :** Vérification via `ArchiveVoter::VIEW` avant de servir le flux.
  * **Images & PDF :** Rendu natif dans une modale front-end (`<iframe src="...">` avec `Content-Disposition: inline; filename="..."`).
  * **Vidéos (`.mp4`, `.webm`) :** Support des **HTTP Range Requests** (`206 Partial Content`) via `BinaryFileResponse` de Symfony pour permettre la lecture fluide et l'avance rapide sans télécharger tout le fichier.
  * **Fichiers Office (`.docx`, `.xlsx`, `.pptx`) :** Intégration d'une visionneuse côté client légère (ex: rendu HTML/JS via `mammoth.js` pour Word, `SheetJS/xlsx` pour Excel) ou conversion serveur à la volée vers PDF via LibreOffice Headless dans le conteneur PHP si installé, afin de garantir l'aperçu même en environnement fermé/intranet.
* **Remplacement / Mise à jour de document :**
  * Lors du remplacement d'un fichier existant, incrémenter `version`, conserver l'ancien fichier dans un sous-dossier `versions/` (ou historiser), mettre à jour `updatedAt`, `updatedBy` et enregistrer une entrée `UPDATE_FILE` dans `ArchiveAuditLog`.

### 4.2. Moteur de Recherche & Export PDF des Archives
* **Recherche multicritère (`/admin/ged/search`) :**
  * Endpoint JSON + interface réactive permettant de filtrer simultanément par : mots-clés (titre, nom original, description), `ArchiveYear`, `ArchiveProject`, type de fichier (PDF, Office, Image, Vidéo), et plage de dates.
  * Indexation SQL (`FULLTEXT` ou `INDEX` composite sur `title`, `original_filename`, `folder_id`) pour des réponses instantanées.
* **Export PDF (`/admin/ged/export/project/{id}` ou `/year/{id}`) :**
  * Utilisation de `dompdf/dompdf` (ou `tecnickcom/tcpdf`) pour générer un **bordereau récapitulatif institutionnel en PDF** (avec logo Bon Pasteur RDC, en-tête officiel, métadonnées du projet, arborescence des dossiers, liste des documents archivés, dates, auteurs et empreintes SHA-256).

### 4.3. Sauvegardes Automatisées & Politique de Conservation
* Créer une commande Symfony CLI : `php bin/console app:backup:run`.
* **Actions de la commande :**
  1. Exécuter `mysqldump` (avec `--single-transaction`) vers `var/backups/db_YYYY-MM-DD_HH-ii.sql.gz`.
  2. Créer une archive `tar.gz` incrémentale ou complète de `var/storage/archives` et `public/uploads`.
  3. Appliquer une politique de rétention automatique (ex: conserver les 7 dernières sauvegardes quotidiennes, 4 hebdomadaires et 12 mensuelles).
* Configurer un service `cron` dans l'infrastructure Docker exécutant cette commande chaque nuit à 02h00, plus un bouton « Lancer une sauvegarde maintenant » dans l'interface `ROLE_ADMIN`.

### 4.4. Multilingue (Français / Anglais)
* **Langue par défaut :** Français (`fr`).
* **Architecture i18n Symfony :** Préfixer les routes publiques par `{_locale}` (`requirements: {'_locale': 'fr|en'}`, défaut `fr`).
* **Interface statique :** Fichiers `translations/messages.fr.yaml` et `translations/messages.en.yaml`.
* **Contenus dynamiques & Traduction automatique :**
  * Intégrer un service `TranslationService` (connectable à une API comme DeepL / Google Translate ou un endpoint libre/configurable via variable d'environnement `TRANSLATION_API_KEY`) permettant de traduire automatiquement à la volée ou de mettre en cache en base (`entity_translations`) les textes français vers l'anglais, avec fallback propre sur le français si la connexion internet est coupée.

### 4.5. Performance & Optimisation Connexion Limitée (RDC / Lualaba)
* **Compression d'images automatique à l'upload :** Redimensionner et convertir les photos uploadées en `.webp` (qualité 80%, largeur max 1600px pour les bannières, 600px pour les vignettes) via `Intervention/Image` ou `GD` afin de réduire le poids des pages de 70 à 85%.
* **Lazy Loading :** Attribut `loading="lazy"` obligatoire sur toutes les images et iframes.
* **Carrousel d'accueil (30 secondes) :** Script JS léger (`home-carousel.js`, < 3 Ko) gérant le défilement automatique toutes les `30000ms` avec préchargement uniquement de la diapositive suivante.
* **Pagination stricte :** Toutes les listes publiques (Projets, Actualités, Témoignages) et internes (Archives) doivent être paginées (`KnpPaginatorBundle` ou pagination Doctrine native, max 12 à 20 éléments par page).

---

## 5. PLAN D'EXÉCUTION PAR PHASES POUR L'AGENT IA

L'agent IA devra exécuter le développement selon l'ordre séquentiel suivant, en validant chaque étape par des tests fonctionnels :

1. **Phase 1 — Socle Docker & Symfony :**
   * Générer `Dockerfile`, `docker-compose.yml`, configuration `nginx/default.conf` et initialiser le projet Symfony 7.x avec les dépendances (`orm`, `twig`, `security-bundle`, `form`, `validator`, `mime`, `uid`, `dompdf`, `tailwind`).
2. **Phase 2 — Modélisation Base de Données & Sécurité :**
   * Créer toutes les entités Doctrine, générer et exécuter les migrations MySQL.
   * Implémenter `User`, le système de connexion sécurisé, les rôles (`ROLE_ADMIN`, `ROLE_COMM`), `ArchiveVoter` et les `DataFixtures` de démonstration (incluant un compte admin initial et les années 2024, 2025, 2026).
3. **Phase 3 — Système d'Archivage Numérique (GED) :**
   * Développer le `FileStorageService` sécurisé (stockage privé hors `public/`, hash SHA-256, validation MIME).
   * Créer l'explorateur d'archives (Années $\rightarrow$ Projets $\rightarrow$ 7 dossiers types $\rightarrow$ sous-dossiers), l'upload multiple, le versioning, la corbeille (Soft Delete), la prévisualisation (PDF, images, streaming vidéo Range Requests, Office), la recherche multicritère JSON et l'export PDF.
4. **Phase 4 — Back-Office d'Administration Unifié :**
   * Concevoir une interface d'administration ergonomique, intuitive et épurée (pensée pour un chargé de communication non-technicien) permettant de gérer toutes les rubriques du site public, les médias et les statistiques de fréquentation.
5. **Phase 5 — Site Vitrine Public & Optimisation Réseau :**
   * Intégrer les vues publiques responsive respectant la charte graphique de l'ASBL Bon Pasteur (Accueil avec carrousel 30s, Qui sommes-nous + Plan stratégique 2024-2028, Projets en cours/finis, Actualités/Recrutements, Partenaires, Contact).
   * Activer le support multilingue `FR/EN`, la compression WebP automatique et la commande cron de sauvegarde.