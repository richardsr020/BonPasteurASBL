import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'list', 'form'];

    connect() {
        this.inputTarget.addEventListener('change', () => this.preview());
    }

    preview() {
        const files = Array.from(this.inputTarget.files || []);
        this.listTarget.textContent = files.length
            ? files.map((file) => `${file.name} (${this.formatSize(file.size)})`).join(', ')
            : '';
    }

    formatSize(bytes) {
        if (bytes < 1024) return `${bytes} o`;
        if (bytes < 1024 * 1024) return `${Math.round(bytes / 1024)} Ko`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} Mo`;
    }

    submit() {
        this.formTarget.requestSubmit();
    }
}
