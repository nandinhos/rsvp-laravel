import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

// Reveal progressivo on scroll via IntersectionObserver (CSS-first).
document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
    );

    items.forEach((el) => observer.observe(el));
});
