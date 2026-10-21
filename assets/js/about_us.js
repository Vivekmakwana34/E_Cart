document.addEventListener('DOMContentLoaded', function () {
    const statNumbers = document.querySelectorAll('.about-stat-number');
    const faqItems = document.querySelectorAll('.about-faq-item');

    function animateNumber(el, target, suffix) {
        const duration = 1200;
        const start = 0;
        const startTime = performance.now();

        function frame(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const value = Math.floor(start + (target - start) * progress);
            el.textContent = value + suffix;
            if (progress < 1) {
                requestAnimationFrame(frame);
            }
        }

        requestAnimationFrame(frame);
    }

    function animateStats() {
        statNumbers.forEach(function (el) {
            const targetAttr = el.getAttribute('data-target');
            const suffix = el.getAttribute('data-suffix') || '';
            const target = targetAttr ? parseFloat(targetAttr) : 0;
            if (!isNaN(target)) {
                animateNumber(el, target, suffix);
            }
        });
    }

    if (statNumbers.length) {
        let done = false;
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && !done) {
                        done = true;
                        animateStats();
                        observer.disconnect();
                    }
                });
            }, { threshold: 0.3 });

            statNumbers.forEach(function (el) {
                observer.observe(el);
            });
        } else {
            animateStats();
        }
    }

    faqItems.forEach(function (item) {
        const question = item.querySelector('.about-faq-question');
        if (!question) return;
        question.addEventListener('click', function () {
            item.classList.toggle('open');
        });
    });
});
