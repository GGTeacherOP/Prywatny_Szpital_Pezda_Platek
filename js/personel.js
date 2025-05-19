document.addEventListener('DOMContentLoaded', () => {
    console.log('personel.js loaded');

    // Elementy DOM
    const staffCards = document.querySelectorAll('.staff-card');

    // Animacje przy przewijaniu z opóźnieniem
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate-in');
                }, index * 100);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });

    staffCards.forEach(card => {
        observer.observe(card);
    });

    // Zaawansowany efekt hover na kartach
    staffCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.classList.add('hover');
            const img = card.querySelector('img');
            if (img) {
                img.style.transform = 'scale(1.1) rotate(5deg)';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            card.classList.remove('hover');
            const img = card.querySelector('img');
            if (img) {
                img.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });

    // Dodanie przycisku "Powrót na górę"
    const backToTopBtn = document.createElement('button');
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '↑';
    document.body.appendChild(backToTopBtn);

    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });

    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}); 