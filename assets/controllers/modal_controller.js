import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['frame', 'title'];

    open(event) {
        const { url, title } = event.currentTarget.dataset;
        this.frameTarget.src = url;
        this.titleTarget.textContent = title || 'Aperçu du document';
        this.element.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    close() {
        this.frameTarget.src = 'about:blank';
        this.element.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}
