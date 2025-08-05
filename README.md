# Projet OpenClassRooms Tom Troc

TomTroc est une application web développée dans le cadre de la formation OpenClassRooms. Elle permet aux utilisateurs de gérer une bibliothèque personnelle, d’échanger des livres, et de communiquer via un système de messagerie intégré.

Le projet repose sur le framework **Metroid**, un micro-framework PHP conçu pour les projets pédagogiques et modulaires.

---

## ⚙️ Fonctionnalités principales

- 🔐 Authentification des utilisateurs (inscription, connexion)
- 📚 Gestion des livres (ajout, édition, suppression, image upload)
- 💬 Système complet de messagerie (création de threads, envoi/suppression de messages)
- 📁 Upload de fichiers avec `FileUploaderService`
- 🧲 Validation des formulaires centralisée (`BookFormValidator`, etc.)
- 🧠 Conteneur de services avec autowiring automatique via Reflection
- 🛠 Architecture MVC propre et découpée (controller, model, service, view)

---

## 🧱️ Architecture technique

Le projet utilise :

- `Request` / `Response` personnalisés pour gérer les requêtes HTTP
- `Router` maison avec parsing dynamique des routes (fichier YAML)
- `Launcher` pour centraliser l'exécution, le routage, la récupération des contrôleurs et leur injection
- `ServiceContainer` pour instancier automatiquement les services selon leurs dépendances
- `ViewRenderer` pour l'affichage des vues `.phtml`
- `ErrorHandler` global pour gérer les erreurs 404, 500 et autres exceptions

---

## 🗃️ Organisation des fichiers

```
├── config/
│   └── route.yaml         # Déclaration des routes
├── public/
│   ├── index.php          # Point d'entrée de l'application
│   └── assets/            # CSS, JS, images...
├── src/
│   ├── Controller/        # Contrôleurs principaux
│   ├── Model/             # Accès aux données
│   ├── Services/          # Services (Uploader, Validator...)
│   ├── Form/              # Formulaires et validateurs
│   └── View/              # Rendu des vues (via ViewRenderer)
└── template/              # Fichiers de vues (phtml)
```

---

## 📦 Installation de Metroid

La structure est divisée en **2 dépôts distincts** :

1. [`metroid-webapp`](https://github.com/Corvaxx117/metroid-webapp) → Le cœur du framework (installé via Composer dans `/vendor`)
2. [`metroid-webapp-skeleton`](https://github.com/Corvaxx117/metroid-webapp-skeleton) → Le squelette de projet à la racine

### 🧼 Commande d'installation

```bash
composer create-project corvaxx/metroid-webapp-skeleton mon-projet \
  --repository='{"type":"vcs","url":"https://github.com/Corvaxx117/metroid-webapp-skeleton"}' \
  --stability=dev --prefer-dist
```

---

## 🔧 Ajustements après installation

1. Dans le fichier `config/config.php`, remplacez la valeur de `APP_BASE_URL`
2. Ajoutez un fichier `.htaccess` dans le dossier `public/` si vous utilisez Apache
3. Vérifiez que les permissions d'écriture sont correctes pour le dossier `log/`
4. Créez une base de données SQL avec la structure disponible dans `tom_troc.sql`

---

## 🚫 Fichiers ignorés (`.gitignore`)

Pour éviter de versionner les fichiers sensibles ou lourds :

```gitignore
/.env
.envlocal
/vendor/
composer.lock
/var/
.idea/
.vscode/
.DS_Store
Thumbs.db
/public/assets/images/uploads
/log/
```

---

## 🙋‍♂️ Auteur

Julien – projet OpenClassRooms, parcours Développeur d'applications PHP/Symfony

---

## 🔗 Liens utiles

- [Metroid WebApp (core)](https://github.com/Corvaxx117/metroid-webapp)
- [Metroid Skeleton (squelette projet)](https://github.com/Corvaxx117/metroid-webapp-skeleton)
