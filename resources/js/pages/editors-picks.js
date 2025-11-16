import Alpine from 'alpinejs';

function EditorsPicks() {
    return {
        init() {
            const list = document.getElementById('pick-list');
            if (!list) return;

            const items = Array.from(list.querySelectorAll('li'));
            items.forEach((item) => {
                item.draggable = true;
                item.addEventListener('dragstart', this.onDragStart.bind(this));
                item.addEventListener('dragover', this.onDragOver.bind(this));
                item.addEventListener('drop', this.onDrop.bind(this));
                item.addEventListener('dragend', this.onDragEnd.bind(this));
            });
        },
        draggingEl: null,
        onDragStart(e) {
            this.draggingEl = e.currentTarget;
            e.dataTransfer.effectAllowed = 'move';
            e.currentTarget.classList.add('ring-2', 'ring-indigo-500');
        },
        onDragOver(e) {
            e.preventDefault();
            const target = e.currentTarget;
            if (target === this.draggingEl) return;
            const list = target.parentNode;
            const draggingRect = this.draggingEl.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const next = (e.clientY - targetRect.top) / targetRect.height > 0.5;
            list.insertBefore(this.draggingEl, next ? target.nextSibling : target);
        },
        onDrop(e) {
            e.preventDefault();
            this.syncHiddenInputs();
        },
        onDragEnd(e) {
            e.currentTarget.classList.remove('ring-2', 'ring-indigo-500');
            this.syncHiddenInputs();
        },
        syncHiddenInputs() {
            const list = document.getElementById('pick-list');
            const ids = Array.from(list.children).map((li) => li.getAttribute('data-id'));
            const inputs = list.querySelectorAll('input[name=\"order[]\"]');
            inputs.forEach((input, idx) => {
                input.value = ids[idx];
            });
        },
    };
}

window.EditorsPicks = EditorsPicks;
window.Alpine = Alpine;
Alpine.start();


