# Livre d'Or – KT-Gbook

Application web de livre d'or développée en PHP natif. Elle permet aux visiteurs de déposer un message, avec un workflow de modération complet côté administration.

---

## Stack technique

| Composant | Choix |
|---|---|
| Langage | PHP 8.3+ (strict types) |
| Base de données | SQLite 3 (PDO) |
| Frontend | Bootstrap 5.3 + Bootstrap Icons 1.11 |
| Dépendances | `vlucas/phpdotenv` uniquement |
| Architecture | MVC, front controller unique, router maison |

---

## Fonctionnalités

### Partie publique
- Consultation des messages approuvés avec pagination
- Mise en avant de messages sélectionnés
- Formulaire de dépôt avec email et ville optionnels
- Message soumis à modération avant publication
- Protection anti-spam multicouche (voir [Sécurité](#sécurité))

### Administration
- Authentification par session avec protection CSRF
- Tableau de bord avec compteurs par statut (en attente / approuvé / refusé)
- Recherche par nom, ville, message ou email
- Filtrage par statut
- Actions : approbation, refus, édition, mise en avant, suppression
- Pagination configurable

---

## Prérequis

- PHP ≥ 8.3 avec extensions `pdo_sqlite`, `sqlite3`
- Composer
- Apache avec `mod_rewrite` activé

---

## Installation

```bash
# 1. Installer les dépendances
composer install

# 2. Configurer l'environnement
cp .env.example .env

# 3. Initialiser la base de données
php scripts/init_db.php
```

Pointer le document root d'Apache sur le dossier `public/`.

> **Important :** Modifier immédiatement les identifiants admin par défaut après l'installation (voir ci-dessous).

---

## Configuration

Toute la configuration se fait dans `.env` :

```ini
# Application
APP_NAME="Livre d'Or - KT-Gbook"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.be/

# Pagination
ENTRIES_PER_PAGE=8
ADMIN_ENTRIES_PER_PAGE=12

# Base de données
DB_DATABASE=database/app.sqlite

# Anti-flood formulaire public
GUESTBOOK_MIN_SECONDS=4
GUESTBOOK_RATE_LIMIT_MINUTES=15
GUESTBOOK_RATE_LIMIT_MAX_SUBMISSIONS=3

# Rate limiting connexion admin
LOGIN_RATE_LIMIT_MINUTES=15
LOGIN_RATE_LIMIT_MAX_ATTEMPTS=5

# Cloudflare Turnstile (optionnel)
TURNSTILE_ENABLED=false
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
# Uniquement pour le développement local si APP_ENV=dev 
# Mettre sur true dans tous les autres cas
TURNSTILE_VERIFY_SSL=true
```

---

## Compte admin par défaut

```
Email    : admin@bengalis.local
Mot de passe : admin1234
```

> **À modifier impérativement avant toute mise en production.**
> Changer le hash directement en base via `scripts/init_db.php` ou en base SQLite.

---

## Sécurité

### Formulaire public
| Mécanisme | Détail |
|---|---|
| Honeypot | Champ caché détecté par les bots |
| Délai minimum | Soumission rejetée si trop rapide (`GUESTBOOK_MIN_SECONDS`) |
| Rate limiting IP | Max `N` soumissions par fenêtre glissante, IP hashée en SHA-256 |
| Modération | Aucun message publié sans approbation admin |
| Turnstile | Captcha Cloudflare activable en option |

### Administration
| Mécanisme | Détail |
|---|---|
| CSRF | Token `random_bytes(32)`, comparaison en temps constant |
| Rate limiting login | Max `N` tentatives par IP sur fenêtre glissante |
| Régénération de session | `session_regenerate_id()` à chaque connexion |
| Mots de passe | `password_hash()` / `password_verify()` (algorithme agile) |
| Requêtes SQL | Requêtes préparées systématiques (0 concaténation) |
| XSS | `htmlspecialchars()` sur toutes les sorties via `View::e()` |

### Activer Cloudflare Turnstile

```ini
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=votre_cle_site
TURNSTILE_SECRET_KEY=votre_cle_secrete
```

---

## Structure du projet

```text
KT-Gbook/
├── config/
│   └── bootstrap.php          # Initialisation de l'application
├── database/
│   └── app.sqlite             # Base de données (ignorée par git)
├── public/
│   ├── index.php              # Front controller
│   └── assets/
│       ├── css/app.css
│       ├── js/app.js
│       └── img/
├── scripts/
│   └── init_db.php            # Initialisation du schéma et données de démo
├── src/
│   ├── Config/Config.php      # Accès aux variables d'environnement
│   ├── Controller/
│   │   ├── AuthController.php
│   │   └── GuestbookController.php
│   ├── Core/
│   │   ├── Auth.php
│   │   ├── Csrf.php
│   │   ├── Database.php
│   │   ├── Flash.php
│   │   ├── LoginRateLimiter.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   ├── Turnstile.php
│   │   └── View.php
│   └── Repository/
│       ├── EntryRepository.php
│       └── UserRepository.php
├── templates/
│   ├── layout.php
│   ├── auth/login.php
│   └── guestbook/
│       ├── admin.php
│       ├── edit.php
│       └── index.php
├── .env.example
├── composer.json
└── .htaccess
```

---

## Pistes d'évolution

- Export CSV des messages
- Page publique dédiée aux messages mis en avant
- Notification email à l'admin lors d'une nouvelle soumission
- Gestion multi-utilisateurs avec rôles (éditeur / super-admin)
- Système de migration de base de données
