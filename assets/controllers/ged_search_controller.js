import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'year', 'project', 'type', 'dateFrom', 'dateTo', 'results', 'count', 'spinner'];
    static values = { url: String };

    connect() {
        this.timer = null;
        [this.inputTarget, this.yearTarget, this.projectTarget, this.typeTarget, this.dateFromTarget, this.dateToTarget].forEach((element) => {
            element.addEventListener('input', () => this.schedule());
            element.addEventListener('change', () => this.schedule());
        });
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    schedule() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.search(), 350);
    }

    async search() {
        this.spinnerTarget.classList.remove('hidden');
        const params = new URLSearchParams({
            q: this.inputTarget.value,
            year: this.yearTarget.value,
            project: this.projectTarget.value,
            type: this.typeTarget.value,
            dateFrom: this.dateFromTarget.value,
            dateTo: this.dateToTarget.value,
        });

        try {
            const response = await fetch(`${this.urlValue}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            this.countTarget.textContent = `${data.total} résultat(s)`;
            this.resultsTarget.innerHTML = data.results.length
                ? data.results.map((item) => this.row(item)).join('')
                : '<p class="p-6 text-sm text-slate-500">Aucun document ne correspond aux critères.</p>';
        } catch (error) {
            this.resultsTarget.innerHTML = '<p class="p-6 text-sm text-red-600">La recherche est momentanément indisponible.</p>';
        } finally {
            this.spinnerTarget.classList.add('hidden');
        }
    }

    row(item) {
        const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        })[char]);
        return `<div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-3 last:border-0">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">${escape(item.title)}</p>
                <p class="text-xs text-slate-500">${escape(item.year)} · ${escape(item.project)} · ${escape(item.folder)} · ${escape(item.size)} · v${item.version}</p>
            </div>
            <div class="flex gap-2">
                <a class="bp-btn-outline px-3 py-1 text-xs" href="${item.previewUrl}" target="_blank" rel="noopener">Aperçu</a>
                <a class="bp-btn-primary px-3 py-1 text-xs" href="${item.downloadUrl}">Télécharger</a>
            </div>
        </div>`;
    }
}
