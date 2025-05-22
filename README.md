# 🌱 Metroid – Starter WebApp SKELETON

Un mini-framework PHP MVC moderne, léger et typé, inspiré de Symfony.  
Parfait pour initier rapidement des projets web structurés sans dépendre d’un framework trop lourd.

---

## ✨ Fonctionnalités principales

- 🧱 Architecture MVC claire et modulaire
- 🔁 Routage YAML (`config/route.yaml`)
- 🚀 Contrôleurs typés (avec autoload PSR-4)
- 💬 Objets `Request` et `Response` pour centraliser les flux HTTP
- 🖼️ Système de vues avec `layout.phtml` personnalisable
- ⚡ Services injectés simplement (`ViewRenderer`, `FlashMessage`, etc.)
- ❗ Gestion d’erreurs propre avec vue dédiée
- 🧪 Prêt pour les tests (PHPUnit)

---

## 📦 Installation

La structure est divisée en **2 dépôts distincts** :

1. [`metroid-webapp`](https://github.com/Corvaxx117/metroid-webapp) → Le cœur du framework (installé via Composer dans `/vendor`)
2. [`metroid-webapp-skeleton`](https://github.com/Corvaxx117/metroid-webapp-skeleton) → Le squelette de projet à la racine

## 🧮 Commande d'installation

Une seule commande permet d'installer les deux dépôts

```bash
composer create-project corvaxx/metroid-webapp-skeleton mon-projet \
  --repository='{"type":"vcs","url":"https://github.com/Corvaxx117/metroid-webapp-skeleton"}' \
  --stability=dev --prefer-dist
```

## 🔧 Ajustements

Une fois le projet installé

- Dans le fichier config.php, remplacez l'adresse APP_BASE_URL
- Ajoutez un fichier .htaccess dans le dossier public du squelette
