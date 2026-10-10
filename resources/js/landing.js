import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.addEventListener('DOMContentLoaded', () => {
    initReveal();
    initScrollProgress();
    initParallax();
    initCountUp();
});

/* Reveal + stagger via IntersectionObserver (CSS-first). */
function initReveal() {
    const items = document.querySelectorAll('.reveal, .stagger');

    if (reduceMotion || !('IntersectionObserver' in window)) {
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
        { threshold: 0.12, rootMargin: '0px 0px -48px 0px' },
    );

    items.forEach((el) => observer.observe(el));
}

/* Barra de progresso de leitura no topo. */
function initScrollProgress() {
    const bar = document.getElementById('scroll-progress');
    if (!bar) return;

    const update = () => {
        const h = document.documentElement;
        const scrolled = h.scrollTop / (h.scrollHeight - h.clientHeight);
        bar.style.width = `${Math.min(scrolled * 100, 100)}%`;
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
}

/* Parallax leve nos orbes do hero. */
function initParallax() {
    if (reduceMotion) return;
    const orbs = document.querySelectorAll('[data-parallax]');
    if (!orbs.length) return;

    window.addEventListener(
        'scroll',
        () => {
            const y = window.scrollY;
            orbs.forEach((orb) => {
                const speed = parseFloat(orb.dataset.parallax) || 0.15;
                orb.style.transform = `translateY(${y * speed}px)`;
            });
        },
        { passive: true },
    );
}

/* Count-up animado dos indicadores quando entram na viewport. */
function initCountUp() {
    const nums = document.querySelectorAll('[data-countup]');
    if (!nums.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
        nums.forEach((el) => (el.textContent = el.dataset.countup));
        return;
    }

    const animate = (el) => {
        const target = parseInt(el.dataset.countup, 10);
        const duration = 1100;
        const start = performance.now();
        const step = (now) => {
            const p = Math.min((now - start) / duration, 1);
            // easeOutExpo
            const eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
            el.textContent = Math.round(eased * target).toString();
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.5 },
    );

    nums.forEach((el) => observer.observe(el));
}
