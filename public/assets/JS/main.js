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

  if (messagingApp) {
    const currentUserId = messagingApp.dataset.userId;
    if (currentUserId) {
      const messenger = new Messenger(parseInt(currentUserId, 10));
    } else {
      console.warn("User ID non trouvé dans data-user-id.");
    }
  } else {
    console.warn("messagingApp introuvable.");
  }
});

// document.addEventListener("mousemove", function (e) {
//   let x = (e.clientX / window.innerWidth) * 100;
//   let y = (e.clientY / window.innerHeight) * 100;

//   document.documentElement.style.setProperty("--mouse-x", x + "%");
//   document.documentElement.style.setProperty("--mouse-y", y + "%");
// });

document.querySelectorAll(".custom-alert").forEach((alert) => {
  setTimeout(() => {
    alert.style.opacity = "0";
    setTimeout(() => alert.remove(), 500);
  }, 5000);
});
