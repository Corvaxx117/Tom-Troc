# Projet OpenClassRooms Tom Troc

README en cours de mise à jour ...
Le projet TomTroc est en cours de développement pour la formation OpenClassRooms et à été créé à partir du framework Metroid :

---

## 📦 Installation de Metroid

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
