export default class ImagePreviewer {
  constructor(inputSelector, previewSelector) {
    this.input = document.querySelector(inputSelector);
    this.preview = document.querySelector(previewSelector);

    if (this.input && this.preview) {
      this.input.addEventListener("change", this.updatePreview.bind(this));
    }
  }

  updatePreview() {
    const file = this.input.files[0];

    if (file && file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.preview.src = e.target.result;
        this.preview.style.display = "block"; // au cas où il était masqué
      };
      reader.readAsDataURL(file);
    }
  }
}
