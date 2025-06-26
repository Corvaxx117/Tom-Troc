export default class ModalManager {
  constructor(modalSelector, openBtnSelector, closeBtnSelector) {
    this.modal = document.querySelector(modalSelector);
    this.openBtn = document.querySelector(openBtnSelector);
    this.closeBtn = this.modal?.querySelector(closeBtnSelector);

    if (!this.modal || !this.openBtn || !this.closeBtn) {
      return;
    }

    this.openBtn.addEventListener("click", () => this.open());
    this.closeBtn.addEventListener("click", () => this.close());

    // Ferme la modale si on clique en dehors du contenu
    window.addEventListener("click", (e) => {
      if (e.target === this.modal) {
        this.close();
      }
    });
  }

  open() {
    this.modal.style.display = "block";
  }

  close() {
    this.modal.style.display = "none";
  }
}
