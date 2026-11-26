import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['dropdown'];

    connect() {
        this.onScroll = () => {
            this.element.classList.toggle('is-scrolled', window.scrollY > 12);
        };
        this.onDocumentClick = (event) => {
            if (this.isOpen() && !this.element.contains(event.target)) {
                this.close();
            }
        };
        this.onKeyDown = (event) => {
            if (event.key === 'Escape') this.close();
        };

        window.addEventListener('scroll', this.onScroll, { passive: true });
        document.addEventListener('click', this.onDocumentClick);
        document.addEventListener('keydown', this.onKeyDown);
        this.onScroll();
    }

    disconnect() {
        window.removeEventListener('scroll', this.onScroll);
        document.removeEventListener('click', this.onDocumentClick);
        document.removeEventListener('keydown', this.onKeyDown);
    }

    toggle() {
        this.isOpen() ? this.close() : this.open();
    }

    open() {
        this.dropdownTarget.hidden = false;
        this.element.classList.add('dropdown-open');
        this.element.querySelector('[data-action="site-nav#toggle"]')?.setAttribute('aria-expanded', 'true');
    }

    close() {
        if (!this.dropdownTarget) return;
        this.dropdownTarget.hidden = true;
        this.element.classList.remove('dropdown-open');
        this.element.querySelector('[data-action="site-nav#toggle"]')?.setAttribute('aria-expanded', 'false');
    }

    isOpen() {
        return this.hasDropdownTarget && !this.dropdownTarget.hidden;
    }
}
