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

    // Attache un event listener à chaque item
    const items = list.querySelectorAll(".conversation-item");
    items.forEach((item) => {
      item.addEventListener("click", (e) => {
        e.preventDefault();
        this.loadThread(item);
      });
    });

    // Gestion du hash de l’URL (ex : #thread-4)
    const hash = window.location.hash;
    if (hash.startsWith("#thread-")) {
      const threadId = hash.replace("#thread-", "");
      const item = document.querySelector(`.conversation-item[data-thread-id="${threadId}"]`);
      if (item) {
        this.loadThread(item);
      } else {
        console.warn("Aucune conversation trouvée pour le hash:", threadId);
      }
    }
  }

  /**
   * Met à jour l'en-tête de la conversation en fonction de l'élément item cliqué
   * @param {HTMLElement} item - Élément de la liste de conversations qui a été cliqué
   */
  updateChatHeader(item) {
    const name = item.dataset.name;
    const avatar = item.dataset.avatar;

    const header = document.querySelector(".chat-header");
    const img = header.querySelector(".conversation-avatar");
    const span = header.querySelector(".chat-username");

    if (img && avatar) img.src = avatar;
    if (span && name) span.textContent = name;
  }

  /**
   * Charge les messages d'un thread spécifique via fetch
   * @param {number} threadId - ID du thread à charger
   */
  async loadThread(item) {
    this.updateChatHeader(item);
    const threadId = item.dataset.threadId;
    try {
      const response = await fetch(`${BASE_URL}/thread/${threadId}/messages`);
      const contentType = response.headers.get("content-type");
      if (!response.ok || !contentType?.includes("application/json")) {
        throw new Error("Réponse non valide");
      }
      const data = await response.json();
      this.renderMessages(data.messages, threadId, data.interlocutor);
    } catch (error) {
      this.messagingMain.textContent = ""; // nettoyage minimal
      const errorP = document.createElement("p");
      errorP.className = "error";
      errorP.textContent = "Erreur de chargement des messages.";
      this.messagingMain.appendChild(errorP);
      console.error("Erreur chargement thread:", error);
    }
  }

  /**
   * Affiche tous les messages et attache les événements aux formulaires et liens de suppression
   * @param {Array} messages - Liste des messages à afficher
   * @param {number} threadId - ID du thread courant
   * @param {Object|null} interlocutor - Données de l'interlocuteur (nom, avatar)
   */
  renderMessages(messages, threadId) {
    const threadContainer = document.getElementById("messageThread");
    const formWrapper = document.querySelector(".message-form-wrapper");

    // Nettoie uniquement les parties dynamiques
    while (threadContainer.firstChild) threadContainer.removeChild(threadContainer.firstChild);
    while (formWrapper.firstChild) formWrapper.removeChild(formWrapper.firstChild);

    // Injecte les messages
    messages.forEach((msg) => {
      const messageEl = this.renderMessageItem(msg);
      threadContainer.appendChild(messageEl);
    });

    // Création du formulaire
    const form = document.createElement("form");
    form.className = "message-form";
    form.method = "POST";
    form.action = `${BASE_URL}/thread/${threadId}/send`;

    const input = document.createElement("input");
    input.type = "text";
    input.name = "content";
    input.className = "message-input";
    input.placeholder = "Tapez votre message ici";
    input.required = true;

    const button = document.createElement("button");
    button.type = "submit";
    button.className = "btn-green btn-send-message";
    button.textContent = "Envoyer";

    form.appendChild(input);
    form.appendChild(button);
    formWrapper.appendChild(form);

    form.addEventListener("submit", (e) => this.sendMessage(e, threadId));

    // Liens de suppression
    threadContainer.querySelectorAll(".delete-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const messageId = link.getAttribute("data-message-id");
        const threadId = link.getAttribute("data-thread-id");
        this.deleteMessage(Number(messageId), Number(threadId));
      });
    });

    // Scroll automatique en bas
    threadContainer.scrollTo({
      top: threadContainer.scrollHeight,
      behavior: "smooth",
    });
  }

  /**
   * Génère le DOM pour un message individuel dans le thread
   * @param {Object} msg - Le message à afficher
   * @return {DocumentFragment}
   */
  renderMessageItem(msg) {
    const isSent = msg.auteur === this.currentUserId;
    const isDeleted = msg.is_deleted == 1;
    const formattedTime = this.formatTime(msg.sent_at);

    const fragment = document.createDocumentFragment();

    const meta = document.createElement("div");
    meta.className = "message-meta";

    const time = document.createElement("span");
    time.className = "conversation-time message-time";
    time.textContent = formattedTime;
    meta.appendChild(time);

    if (isSent && !isDeleted) {
      const deleteLink = document.createElement("a");
      deleteLink.href = "#";
      deleteLink.className = "delete-link";
      deleteLink.dataset.messageId = msg.id;
      deleteLink.dataset.threadId = msg.thread_id;
      const em = document.createElement("em");
      em.textContent = "suppr.";
      deleteLink.appendChild(em);
      meta.appendChild(deleteLink);
    }

    fragment.appendChild(meta);

    const message = document.createElement("div");
    message.className = `message-item ${isSent ? "sent" : "received"}`;

    const content = document.createElement("p");
    content.className = "message-content";
    if (isDeleted) {
      const em = document.createElement("em");
      em.textContent = "Ce message a été supprimé";
      content.appendChild(em);
    } else {
      content.textContent = msg.content;
    }

    message.appendChild(content);
    fragment.appendChild(message);

    return fragment;
  }

  /**
   * Formate une date ISO en heure ou date + heure si différent du jour courant
   * @param {string} isoDate
   * @returns {string}
   */
  formatTime(isoDate) {
    const date = new Date(isoDate);
    const now = new Date();
    const sameDay =
      date.getDate() === now.getDate() &&
      date.getMonth() === now.getMonth() &&
      date.getFullYear() === now.getFullYear();

    const hours = date.getHours().toString().padStart(2, "0");
    const minutes = date.getMinutes().toString().padStart(2, "0");

    if (sameDay) return `${hours}:${minutes}`;
    const day = date.getDate().toString().padStart(2, "0");
    const month = (date.getMonth() + 1).toString().padStart(2, "0");
    return `${day}/${month} ${hours}:${minutes}`;
  }

  /**
   * Envoie un message au serveur via fetch
   * @param {Event} event
   * @param {number} threadId
   */
  async sendMessage(event, threadId) {
    event.preventDefault();

    const form = event.target;
    const input = form.querySelector("input[name='content']");
    const content = input.value.trim();
    if (!content) return;

    try {
      const response = await fetch(`${BASE_URL}/thread/${threadId}/messages`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ content }),
      });

      const result = await response.json();
      if (response.ok && result.success) {
        input.value = "";

        const item = document.querySelector(`.conversation-item[data-thread-id="${threadId}"]`);
        if (item) {
          this.loadThread(item);
        } else {
          console.warn("conversation-item non trouvé pour threadId", threadId);
        }
      } else {
        console.error("Échec de l'envoi:", result);
      }
    } catch (error) {
      console.error("Erreur d'envoi du message:", error);
    }
  }

  /**
   * Supprime un message donné après confirmation
   * @param {number} messageId
   * @param {number} threadId
   */
  async deleteMessage(messageId, threadId) {
    if (!confirm("Supprimer ce message ?")) return;

    try {
      const response = await fetch(`${BASE_URL}/message/${messageId}`, {
        method: "DELETE",
      });

      const result = await response.json();
      if (response.ok && result.success) {
        const item = document.querySelector(`.conversation-item[data-thread-id="${threadId}"]`);
        if (item) {
          this.loadThread(item);
        } else {
          console.warn("conversation-item non trouvé pour threadId", threadId);
        }
      } else {
        console.error("Échec de la suppression:", result);
      }
    } catch (error) {
      console.error("Erreur lors de la suppression du message:", error);
    }
  }
}
