# Livre d'Or – Les Bengalis de Liège

Mini application PHP inspirée de **KT-Drop**, en gardant la même philosophie technique :

- PHP 8.3
- SQLite
- front controller unique
- router maison
- sessions + auth admin
- CSRF
- Bootstrap 5.3
- structure simple et légère

## Fonctionnalités incluses

### Partie publique
- consultation des messages **approuvés**
- pagination
- formulaire de dépôt d'un message
- email et ville optionnels
- message publié seulement après validation
- protection anti-spam avec **honeypot + délai minimum + limitation des envois**
- **Cloudflare Turnstile** en option

### Partie administration
- connexion admin
- tableau de modération
- filtres par texte / statut
- approbation
- refus
- suppression
- mise en avant d'un message
- compteurs par statut

## Structure

```text
bengalis-livre-or-starter/
├── config/
├── database/
├── public/
│   ├── assets/
│   └── index.php
├── scripts/
│   └── init_db.php
├── src/
│   ├── Config/
│   ├── Controller/
│   ├── Core/
│   └── Repository/
└── templates/
```

## Installation

1. Dézipper le projet.
2. Copier `.env.example` en `.env`.
3. Lancer :

```bash
composer install
php scripts/init_db.php
```

4. Pointer Apache sur le dossier du projet.
5. Ouvrir l'application.

## Compte admin par défaut

```text
admin@bengalis.local
admin1234
```

À changer immédiatement dans un vrai environnement.

## Protection anti-spam

### Ce qui est déjà actif
- champ piège honeypot
- délai minimum avant envoi
- limitation par IP hashée sur une fenêtre glissante
- modération obligatoire avant publication

### Activer Cloudflare Turnstile

Dans `.env` :

```ini
TURNSTILE_ENABLED=true
TURNSTILE_SITE_KEY=...votre_cle_site...
TURNSTILE_SECRET_KEY=...votre_cle_secrete...
```

Par défaut, le starter est livré avec :

```ini
TURNSTILE_ENABLED=false
```

### Réglages utiles

```ini
GUESTBOOK_MIN_SECONDS=4
GUESTBOOK_RATE_LIMIT_MINUTES=15
GUESTBOOK_RATE_LIMIT_MAX_SUBMISSIONS=3
```

## Idées d'évolution

- édition des messages par l'admin
- export CSV
- page "messages mis en avant"
- notification email lorsqu'un nouveau message arrive
- charte graphique encore plus proche du site réel avec logo officiel et photos

## Remarques

Le style visuel reprend l'esprit du site : **fond beige clair**, **accents or chaud**, **touches rouge-orangé**, ambiance sobre et chorale/patrimoine.
