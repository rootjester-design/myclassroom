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

      // Ensure event listeners are attached after canvas exists
      this.setupEventListeners(container);
    });
  }

  setupCanvas() {
    const parentRect = this.canvas.parentElement ? this.canvas.parentElement.getBoundingClientRect() : { width: 500, height: 500 };
    const w = Math.max(100, Math.round(parentRect.width));

    this.canvas.width = w;
    if (this.aspectRatio && typeof this.aspectRatio === 'number' && this.aspectRatio > 0) {
      // aspectRatio = width / height
      this.canvas.height = Math.max(100, Math.round(w / this.aspectRatio));
    } else {
      // default to square canvas
      this.canvas.height = w;
    }

    // Start with zoom that fits the image into the canvas
    this.zoom = Math.min(this.canvas.width / this.image.width, this.canvas.height / this.image.height);
    if (!isFinite(this.zoom) || this.zoom <= 0) this.zoom = 1;

    // Center the image
    this.offsetX = 0;
    this.offsetY = 0;
  }

  setupEventListeners(container) {
    // Remove previous listeners if any
    if (this._handlers) {
      Object.keys(this._handlers).forEach(k => {
        this.canvas.removeEventListener(k, this._handlers[k]);
      });
    }

    this._handlers = {};
    this._handlers.mousedown = (e) => this.onMouseDown(e);
    this._handlers.mousemove = (e) => this.onMouseMove(e);
    this._handlers.mouseup = () => this.onMouseUp();
    this._handlers.mouseleave = () => this.onMouseUp();
    this._handlers.wheel = (e) => this.onWheel(e);

    this.canvas.addEventListener('mousedown', this._handlers.mousedown);
    this.canvas.addEventListener('mousemove', this._handlers.mousemove);
    this.canvas.addEventListener('mouseup', this._handlers.mouseup);
    this.canvas.addEventListener('mouseleave', this._handlers.mouseleave);
    this.canvas.addEventListener('wheel', this._handlers.wheel, { passive: false });
    
    // Touch support for mobile
    this._handlers.touchstart = (e) => this.onTouchStart(e);
    this._handlers.touchmove = (e) => this.onTouchMove(e);
    this._handlers.touchend = () => this.onMouseUp();
    this._handlers.touchcancel = () => this.onMouseUp();
    this.canvas.addEventListener('touchstart', this._handlers.touchstart, { passive: false });
    this.canvas.addEventListener('touchmove', this._handlers.touchmove, { passive: false });
    this.canvas.addEventListener('touchend', this._handlers.touchend);
    this.canvas.addEventListener('touchcancel', this._handlers.touchcancel);

    const zoomSlider = container.querySelector('#cropperZoom');
    if (zoomSlider) {
      this._handlers.zoominput = (e) => { this.zoom = parseFloat(e.target.value); this.draw(); };
      zoomSlider.addEventListener('input', this._handlers.zoominput);
      // set initial slider value
      zoomSlider.value = this.zoom.toFixed(2);
    }

    const resetBtn = container.querySelector('#cropperReset');
    if (resetBtn) {
      this._handlers.resetclick = () => this.reset();
      resetBtn.addEventListener('click', this._handlers.resetclick);
    }
  }

  draw() {
    this.ctx.fillStyle = '#ffffff';
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
    const rect = this.canvas.getBoundingClientRect();
    this.dragStartX = (typeof e.offsetX === 'number') ? e.offsetX : (e.clientX - rect.left);
    this.dragStartY = (typeof e.offsetY === 'number') ? e.offsetY : (e.clientY - rect.top);
  }

  onMouseMove(e) {
    if (!this.isDragging) return;
    const rect = this.canvas.getBoundingClientRect();
    const x = (typeof e.offsetX === 'number') ? e.offsetX : (e.clientX - rect.left);
    const y = (typeof e.offsetY === 'number') ? e.offsetY : (e.clientY - rect.top);
    const dx = x - this.dragStartX;
    const dy = y - this.dragStartY;
    this.offsetX += dx / this.zoom;
    this.offsetY += dy / this.zoom;
    this.dragStartX = x;
    this.dragStartY = y;
    this.draw();
  }

  onMouseUp() {
    this.isDragging = false;
  }

  onTouchStart(e) {
    if (e.touches.length === 1) {
      e.preventDefault();
      this.isDragging = true;
      const rect = this.canvas.getBoundingClientRect();
      this.dragStartX = e.touches[0].clientX - rect.left;
      this.dragStartY = e.touches[0].clientY - rect.top;
    }
  }

  onTouchMove(e) {
    if (e.touches.length === 1 && this.isDragging) {
      e.preventDefault();
      const rect = this.canvas.getBoundingClientRect();
      const x = e.touches[0].clientX - rect.left;
      const y = e.touches[0].clientY - rect.top;
      const dx = x - this.dragStartX;
      const dy = y - this.dragStartY;
      this.offsetX += dx / this.zoom;
      this.offsetY += dy / this.zoom;
      this.dragStartX = x;
      this.dragStartY = y;
      this.draw();
    }
  }

  onWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    this.zoom = Math.max(this.minZoom, Math.min(this.maxZoom, this.zoom + delta));
    // update local zoom slider if present
    const slider = this.canvas.parentElement ? this.canvas.parentElement.querySelector('#cropperZoom') : null;
    if (slider) slider.value = this.zoom.toFixed(2);
    this.draw();
  }

  reset() {
    this.zoom = Math.min(this.canvas.width / this.image.width, this.canvas.height / this.image.height);
    this.offsetX = 0;
    this.offsetY = 0;
    const slider = this.canvas.parentElement ? this.canvas.parentElement.querySelector('#cropperZoom') : null;
    if (slider) slider.value = this.zoom.toFixed(2);
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
