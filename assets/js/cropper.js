// Simple Image Cropper/Positioner
class ImageCropper {
  constructor(options = {}) {
    this.imageFile = null;
    this.canvas = null;
    this.ctx = null;
    this.image = null;
    this.zoom = options.zoom || 1;
    this.offsetX = 0;
    this.offsetY = 0;
    this.isDragging = false;
    this.dragStartX = 0;
    this.dragStartY = 0;
    this.aspectRatio = options.aspectRatio || null; // e.g., 1 for square, 16/9 for banner
    this.minZoom = 0.5;
    this.maxZoom = 3;
  }

  init(container, imageFile, options = {}) {
    return new Promise((resolve) => {
      this.imageFile = imageFile;
      this.canvas = container.querySelector('#cropperCanvas');
      this.ctx = this.canvas.getContext('2d');
      
      const reader = new FileReader();
      reader.onload = (e) => {
        this.image = new Image();
        this.image.onload = () => {
          this.setupCanvas();
          this.draw();
          resolve();
        };
        this.image.src = e.target.result;
      };
      reader.readAsDataURL(imageFile);

      this.setupEventListeners(container);
    });
  }

  setupCanvas() {
    const rect = this.canvas.parentElement.getBoundingClientRect();
    this.canvas.width = rect.width;
    this.canvas.height = rect.width; // Square canvas
    this.zoom = Math.min(this.canvas.width / this.image.width, this.canvas.height / this.image.height);
  }

  setupEventListeners(container) {
    this.canvas.addEventListener('mousedown', (e) => this.onMouseDown(e));
    this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
    this.canvas.addEventListener('mouseup', () => this.onMouseUp());
    this.canvas.addEventListener('wheel', (e) => this.onWheel(e));
    
    const zoomSlider = container.querySelector('#cropperZoom');
    if (zoomSlider) {
      zoomSlider.addEventListener('input', (e) => {
        this.zoom = parseFloat(e.target.value);
        this.draw();
      });
    }

    const resetBtn = container.querySelector('#cropperReset');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => this.reset());
    }
  }

  draw() {
    this.ctx.fillStyle = '#000';
    this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

    this.ctx.save();
    this.ctx.translate(this.canvas.width / 2, this.canvas.height / 2);
    this.ctx.scale(this.zoom, this.zoom);
    this.ctx.translate(-this.image.width / 2 + this.offsetX, -this.image.height / 2 + this.offsetY);
    this.ctx.drawImage(this.image, 0, 0);
    this.ctx.restore();
  }

  onMouseDown(e) {
    this.isDragging = true;
    this.dragStartX = e.offsetX;
    this.dragStartY = e.offsetY;
  }

  onMouseMove(e) {
    if (!this.isDragging) return;
    const dx = e.offsetX - this.dragStartX;
    const dy = e.offsetY - this.dragStartY;
    this.offsetX += dx / this.zoom;
    this.offsetY += dy / this.zoom;
    this.dragStartX = e.offsetX;
    this.dragStartY = e.offsetY;
    this.draw();
  }

  onMouseUp() {
    this.isDragging = false;
  }

  onWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    this.zoom = Math.max(this.minZoom, Math.min(this.maxZoom, this.zoom + delta));
    document.getElementById('cropperZoom').value = this.zoom.toFixed(2);
    this.draw();
  }

  reset() {
    this.zoom = Math.min(this.canvas.width / this.image.width, this.canvas.height / this.image.height);
    this.offsetX = 0;
    this.offsetY = 0;
    document.getElementById('cropperZoom').value = this.zoom.toFixed(2);
    this.draw();
  }

  getBlob() {
    return new Promise((resolve) => {
      this.canvas.toBlob(blob => resolve(blob), 'image/jpeg', 0.95);
    });
  }
}

// Global cropper instance
let currentCropper = null;

function openImageCropper(file, options = {}) {
  const modal = document.getElementById('imageCropperModal');
  if (!modal) {
    console.error('Image cropper modal not found');
    return;
  }

  openModal('imageCropperModal');
  currentCropper = new ImageCropper(options);
  currentCropper.init(modal, file, options).then(() => {
    console.log('Cropper initialized');
  });
}

async function saveImage() {
  if (!currentCropper) return;
  const blob = await currentCropper.getBlob();
  if (window.onImageCropped) {
    window.onImageCropped(blob);
  }
  closeModal('imageCropperModal');
}
