import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slide'];
    static values = { interval: { type: Number, default: 30000 } };

    connect() {
        this.index = 0;
        this.show(0);
        this.timer = setInterval(() => this.next(), this.intervalValue);
    }

    disconnect() {
        clearInterval(this.timer);
    }

    next() {
        this.show((this.index + 1) % this.slideTargets.length);
    }

    previous() {
        this.show((this.index - 1 + this.slideTargets.length) % this.slideTargets.length);
    }

    goTo(event) {
        this.show(Number(event.currentTarget.dataset.index));
    }

    show(index) {
        if (!this.slideTargets.length) return;
        this.index = index;
        this.slideTargets.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== index);
            slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
        });
        const next = this.slideTargets[(index + 1) % this.slideTargets.length];
        if (next) {
            const image = next.querySelector('img');
            if (image && image.dataset.src) {
                image.src = image.dataset.src;
                delete image.dataset.src;
            }
        }
    }
}
