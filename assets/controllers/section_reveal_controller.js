import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { interval: { type: Number, default: 10000 } };

    connect() {
        this.sections = Array.from(this.element.querySelectorAll('section'));
        if (!this.sections.length) return;

        this.sections.forEach((section, index) => {
            section.classList.add('reveal-section');
            section.style.setProperty('--reveal-delay', `${Math.min(index, 5) * 80}ms`);
        });

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                entry.target.classList.toggle('is-visible', entry.isIntersecting);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -48px 0px' });

        this.sections.forEach((section) => this.observer.observe(section));
        this.timer = setInterval(() => this.replay(), this.intervalValue);
    }

    disconnect() {
        if (!this.observer) return;
        this.observer.disconnect();
        clearInterval(this.timer);
    }

    replay() {
        this.sections.forEach((section) => {
            if (!section.classList.contains('is-visible')) return;
            section.classList.remove('is-visible');
            void section.offsetWidth;
            section.classList.add('is-visible');
        });
    }
}
