function initMobileMenus() {
    document.querySelectorAll('[data-menu-mobile]').forEach((container) => {
        const toggle = container.querySelector('[data-menu-toggle]');
        const panel = container.querySelector('[data-menu-panel]');
        if (!toggle || !panel) {
            return;
        }
        toggle.addEventListener('click', () => {
            const isHidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden', !isHidden);
            toggle.setAttribute('aria-expanded', String(isHidden));
        });
    });
}

document.addEventListener('DOMContentLoaded', initMobileMenus);


