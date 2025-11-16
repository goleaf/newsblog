// Homepage-specific JavaScript
import Alpine from 'alpinejs';

export default {
    init() {
        // Parallax for hero (desktop only, respect reduced motion)
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isDesktop = () => window.innerWidth >= 1024;
        const hero = document.querySelector('.js-parallax-hero');
        if (!hero) { return; }
        if (prefersReduced) { return; }

        let ticking = false;
        const maxTranslate = 60; // px cap for subtle effect

        const onScroll = () => {
            if (!isDesktop()) { 
                hero.style.transform = '';
                return; 
            }
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const y = window.scrollY || window.pageYOffset;
                    // Move at 20% of scroll for gentle parallax
                    const translate = Math.max(-maxTranslate, Math.min(maxTranslate, y * 0.2));
                    hero.style.willChange = 'transform';
                    hero.style.transform = `translate3d(0, ${translate}px, 0)`;
                    ticking = false;
                });
                ticking = true;
            }
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);
        onScroll();
    }
};
