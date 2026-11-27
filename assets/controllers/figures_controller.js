import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item', 'value'];
    static values = {
        interval: { type: Number, default: 5000 },
        duration: { type: Number, default: 1400 },
    };

    connect() {
        this.locale = document.documentElement.lang || 'fr';
        this.frames = new Map();
        this.timeouts = [];
        this.parsed = this.valueTargets.map((element) => this.parse(element.dataset.figuresValue || element.textContent.trim()));

        this.run();
        this.timer = setInterval(() => this.run(), this.intervalValue);
    }

    disconnect() {
        clearInterval(this.timer);
        this.frames.forEach((frame) => cancelAnimationFrame(frame));
        this.timeouts.forEach((timeout) => clearTimeout(timeout));
    }

    run() {
        this.frames.forEach((frame) => cancelAnimationFrame(frame));
        this.frames.clear();

        this.itemTargets.forEach((item, index) => {
            item.classList.remove('is-animating');
            void item.offsetWidth;
            item.classList.add('is-animating');
            this.timeouts.push(setTimeout(() => item.classList.remove('is-animating'), this.durationValue + index * 120));
        });

        this.parsed.forEach((figure, index) => {
            if (figure.number === null) return;

            const element = this.valueTargets[index];
            const start = performance.now() + index * 120;

            const tick = (now) => {
                if (now < start) {
                    this.frames.set(element, requestAnimationFrame(tick));
                    return;
                }

                const progress = Math.min((now - start) / this.durationValue, 1);
                element.textContent = this.format(figure, this.ease(progress));

                if (progress < 1) {
                    this.frames.set(element, requestAnimationFrame(tick));
                } else {
                    this.frames.delete(element);
                }
            };

            this.frames.set(element, requestAnimationFrame(tick));
        });
    }

    parse(raw) {
        const match = raw.match(/^([^\d]*)(\d[\d\s\u00a0\u202f.,]*)(.*)$/);
        if (!match) return { number: null, prefix: '', suffix: raw, decimals: 0 };

        const prefix = match[1];
        const suffix = match[3];
        const comma = match[2].lastIndexOf(',');
        const dot = match[2].lastIndexOf('.');
        const separator = comma > dot ? ',' : '.';
        const numberText = match[2].replace(/[\s\u00a0\u202f]/g, '');
        const parts = numberText.replace(separator === ',' ? /\./g : /,/g, '').split(separator);
        const isDecimal = parts.length === 2 && parts[1].length !== 3;

        return {
            number: Number(isDecimal ? parts.join('.') : parts.join('')),
            prefix,
            suffix,
            decimals: isDecimal ? parts[1].length : 0,
        };
    }

    format(figure, progress) {
        const value = figure.number * progress;
        const factor = 10 ** figure.decimals;
        const rounded = Math.round(value * factor) / factor;
        const formatter = new Intl.NumberFormat(this.locale, {
            minimumFractionDigits: figure.decimals,
            maximumFractionDigits: figure.decimals,
        });

        return `${figure.prefix}${formatter.format(rounded)}${figure.suffix}`;
    }

    ease(progress) {
        return 1 - (1 - progress) ** 3;
    }
}
