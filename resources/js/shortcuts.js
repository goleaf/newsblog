// Global keyboard shortcuts
// - "/" opens search modal
// - "?" opens shortcuts help modal
// - "n"/"p" navigate next/previous pagination
// Guards: ignore when typing in inputs/textareas/contenteditable; respect open modals

function isTypingContext(target) {
    if (!target) { return false; }
    const tag = (target.tagName || '').toLowerCase();
    const editable = target.isContentEditable;
    return editable || tag === 'input' || tag === 'textarea' || tag === 'select';
}

function anyModalOpen() {
    const openSearch = !!document.querySelector('[x-data] [x-show="open"]:not([x-cloak])');
    const alpineModals = Array.from(document.querySelectorAll('[data-modal-id]'))
        .some(el => getComputedStyle(el).display !== 'none');
    return openSearch || alpineModals;
}

function openSearchModal() {
    window.dispatchEvent(new CustomEvent('open-search'));
}

function openShortcutsModal() {
    if (window.Alpine && Alpine.store && Alpine.store('modal')) {
        Alpine.store('modal').open('shortcuts-help');
    } else {
        // Fallback: emit event the component listens to
        window.dispatchEvent(new CustomEvent('open-shortcuts-help'));
    }
}

function navigatePagination(direction) {
    const container = document;
    // Prefer rel attributes
    const relSelector = direction === 'next' ? 'a[rel="next"]' : 'a[rel="prev"], a[rel="previous"]';
    let link = container.querySelector(relSelector);
    if (!link) {
        // Try aria-labels common in Tailwind pagination
        const ariaLabels = direction === 'next'
            ? ['Next', 'Next »', 'Next ›', 'Next >', 'Next Page']
            : ['Previous', '« Previous', '‹ Previous', '< Previous', 'Previous Page'];
        link = Array.from(container.querySelectorAll('a[aria-label], button[aria-label]'))
            .find(a => ariaLabels.includes(a.getAttribute('aria-label')));
    }
    if (!link) {
        // Fallback by text content scan
        const texts = direction === 'next'
            ? ['Next', 'Next »', 'Next ›', 'Next >']
            : ['Previous', '« Previous', '‹ Previous', '< Previous'];
        link = Array.from(container.querySelectorAll('a'))
            .find(a => texts.some(t => a.textContent.trim() === t));
    }
    if (link && link.href) {
        window.location.href = link.href;
    }
}

document.addEventListener('keydown', (e) => {
    const key = e.key;
    if (isTypingContext(e.target)) { return; }

    // "Esc" is handled by modal components already

    if (key === '/') {
        e.preventDefault();
        if (!anyModalOpen()) {
            openSearchModal();
        }
        return;
    }

    if (key === '?') {
        e.preventDefault();
        openShortcutsModal();
        return;
    }

    if ((key === 'n' || key === 'N') && !anyModalOpen()) {
        e.preventDefault();
        navigatePagination('next');
        return;
    }

    if ((key === 'p' || key === 'P') && !anyModalOpen()) {
        e.preventDefault();
        navigatePagination('prev');
        return;
    }
});


