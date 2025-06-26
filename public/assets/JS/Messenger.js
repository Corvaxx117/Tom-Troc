export default class Messenger {
  /**
   * Initialise la classe Messenger
   * @param {number} currentUserId - ID de l'utilisateur connecté
   */
  constructor(currentUserId) {
    this.currentUserId = currentUserId;
    this.messagingMain = document.getElementById("messagingMain");
    this.init();
  }

  /**
   * Initialise les écouteurs d'événements pour les conversations
   */
  init() {
    const list = document.getElementById("conversationList");
    if (!list) return;

    list.addEventListener("click", (e) => {
      const item = e.target.closest(".conversation-item"); // Trouve l’élément cliqué
      if (item) {
        e.preventDefault();
        const threadId = item.getAttribute("data-thread-id"); // Récupère l’ID du thread
        this.loadThread(threadId); // Charge les messages de cette conversation
      }
    });
  }

  /**
   * Charge les messages d'un thread spécifique via fetch
   * @param {number} threadId - ID du thread à charger
   */
  async loadThread(threadId) {
    // On vérifie que le serveur a bien répondu un contenu JSON.
    try {
      // Requête AJAX pour charger les messages
      const response = await fetch(`${BASE_URL}/messages/thread/${threadId}`);
      const contentType = response.headers.get("content-type");
      if (!response.ok || !contentType?.includes("application/json")) {
        throw new Error("Réponse non valide");
      }

      const data = await response.json(); // On transforme la réponse JSON
      this.renderMessages(data.messages, threadId); // Affiche les messages dans le HTML
    } catch (error) {
      this.messagingMain.innerHTML = "<p class='error'>Erreur de chargement des messages.</p>";
      console.error("Erreur chargement thread:", error);
    }
  }

  /**
   * Affiche tous les messages et attache les événements aux formulaires et liens de suppression
   * @param {Array} messages - Liste des messages à afficher
   * @param {number} threadId - ID du thread courant
   */
  renderMessages(messages, threadId) {
    // On injecte le fil de discussion + le formulaire dans la page.

    // !!! Eviter innerHTML !!! , passer par un template (objet de l'api js utilisée pour injecter du contenu)
    //  ou create Element / append ?
    this.messagingMain.innerHTML = `
      <div class="message-thread" id="messageThread">
        ${messages.map((msg) => this.renderMessageItem(msg)).join("")}
      </div>
      <form action="${BASE_URL}/messages/${threadId}/send" method="POST" class="message-form">
        <input type="text" name="content" class="message-input" placeholder="Tapez votre message ici" required>
        <button type="submit" class="btn-green">Envoyer</button>
      </form>
    `;

    // Gère la soumission du formulaire d'envoi de message
    const form = this.messagingMain.querySelector(".message-form");
    form.addEventListener("submit", (e) => this.sendMessage(e, threadId));

    // Ajoute les événements de suppression aux liens 'suppr.'
    const deleteLinks = this.messagingMain.querySelectorAll(".delete-link");
    deleteLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const messageId = link.getAttribute("data-message-id");
        const threadId = link.getAttribute("data-thread-id");
        this.deleteMessage(Number(messageId), Number(threadId));
      });
    });

    // Fait défiler la zone de messages tout en bas
    const threadContainer = this.messagingMain.querySelector("#messageThread");
    if (threadContainer) {
      threadContainer.scrollTo({ top: threadContainer.scrollHeight, behavior: "smooth" });
    }
  }

  /**
   * Génère le HTML pour un message individuel dans le thread
   * @param {Object} msg - Le message à afficher
   * @return {string} - HTML du message
   */
  renderMessageItem(msg) {
    const isSent = msg.auteur === this.currentUserId; // Est-ce que le message vient de moi ?
    const isDeleted = msg.is_deleted == 1; // S'il est supprimé, le contenu est vide
    const formattedTime = this.formatTime(msg.sent_at); // On formate la date d’envoi

    // Affichage des métadonnées (heure et lien suppr.)
    const metaSection = `
      <div class="message-meta">
        <span class="message-time">${formattedTime}</span>
        ${
          isSent && !isDeleted
            ? `<a href="#" class="delete-link" data-message-id="${msg.id}" data-thread-id="${msg.thread_id}"><em>suppr.</em></a>`
            : ""
        }
      </div>
    `;

    // Affichage de la bulle du message
    const messageBubble = `
      <div class="message-item ${isSent ? "sent" : "received"}">
        <p class="message-content">${
          isDeleted ? "<em>Ce message a été supprimé</em>" : msg.content
        }</p>
      </div>
    `;
    // On renvoie les deux blocs HTML concaténés
    return metaSection + messageBubble;
  }

  /**
   * Envoie un message au serveur via fetch
   * @param {Event} event - L'événement de soumission du formulaire
   * @param {number} threadId - ID du thread cible
   */
  async sendMessage(event, threadId) {
    event.preventDefault(); // Empêche le formulaire de recharger la page
    const form = event.target;
    const input = form.querySelector("input[name='content']");
    const content = input.value.trim(); // Supprime les espaces inutiles
    if (!content) return false; // Ne rien envoyer si vide

    // Envoi des données au serveur via fetch()
    try {
      const response = await fetch(`${BASE_URL}/messages/${threadId}/send`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ content }),
      });

      const result = await response.json();
      if (response.ok && result.success) {
        input.value = ""; // Vide le champ
        this.loadThread(threadId); // Recharge le thread avec le nouveau message
      } else {
        console.error("Échec de l'envoi :", result);
      }
    } catch (error) {
      console.error("Erreur d'envoi du message :", error);
    }

    return false;
  }

  /**
   * Supprime un message donné après confirmation
   * @param {number} messageId - ID du message à supprimer
   * @param {number} threadId - ID du thread parent
   */
  async deleteMessage(messageId, threadId) {
    if (!confirm("Supprimer ce message ?")) return;

    try {
      const response = await fetch(`${BASE_URL}/messages/delete/${messageId}`, {
        method: "POST",
      });

      const result = await response.json();

      if (response.ok && result.success) {
        this.loadThread(threadId); // On recharge les messages
      } else {
        console.error("Erreur suppression :", result);
      }
    } catch (error) {
      console.error("Erreur de suppression :", error);
    }
  }

  /**
   * Formate une date ISO en heure ou date + heure si différent du jour courant
   * @param {string} isoDate - Date ISO
   * @returns {string} - Date formatée
   */
  formatTime(isoDate) {
    const date = new Date(isoDate);
    const now = new Date();
    const sameDay =
      date.getDate() === now.getDate() &&
      date.getMonth() === now.getMonth() &&
      date.getFullYear() === now.getFullYear();

    // Si c’est aujourd’hui, on montre juste l’heure
    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");

    if (sameDay) {
      return `${hours}:${minutes}`;
    }
    // Sinon, on affiche aussi le jour/mois
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    return `${day}/${month} ${hours}:${minutes}`;
  }
}
