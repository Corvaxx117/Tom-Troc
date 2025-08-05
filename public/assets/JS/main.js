import ImagePreviewer from "./ImagePreviewer.js";
import ModalManager from "./ModalManager.js";
import Messenger from "./Messenger.js";

document.addEventListener("DOMContentLoaded", () => {
  // Profil utilisateur
  new ImagePreviewer("#avatarInput", "#avatarPreview");

  // Livre (création ou édition)
  new ImagePreviewer("#bookImageInput", "#bookImagePreview");

  // Modale
  new ModalManager("#bookModal", ".btn-add-book", ".close");

  let messenger = null;

  const messagingApp = document.getElementById("messagingApp");
  if (!messagingApp) return;

  const currentUserId = messagingApp.dataset.userId;
  if (currentUserId) {
    new Messenger(parseInt(currentUserId, 10));
  } else {
    console.warn("User ID non trouvé dans data-user-id.");
  }
});

document.querySelectorAll(".custom-alert").forEach((alert) => {
  setTimeout(() => {
    alert.style.opacity = "0";
    setTimeout(() => alert.remove(), 500);
  }, 5000);
});
