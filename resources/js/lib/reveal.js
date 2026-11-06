let observer;

const reveal = {
    mounted(element) {
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        // Already visible content stays visible, including on route changes and reloads.
        if (element.getBoundingClientRect().top < window.innerHeight) return;
        observer ||= new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                entry.target.classList.add('scroll-reveal');
                observer.unobserve(entry.target);
            }
        }, { rootMargin: '0px 0px -24px 0px' });
        observer.observe(element);
    },
    unmounted(element) {
        observer?.unobserve(element);
    },
};

export default reveal;
