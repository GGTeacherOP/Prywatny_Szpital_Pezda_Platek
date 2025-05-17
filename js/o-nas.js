document.addEventListener('DOMContentLoaded', () => {
    // Animacja liczników
    const achievementNumbers = document.querySelectorAll('.achievement-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const value = parseInt(target.dataset.value);
                animateCounter(target, 0, value, 2000);
                observer.unobserve(target);
            }
        });
    }, { threshold: 0.5 });

    achievementNumbers.forEach(number => {
        observer.observe(number);
    });

    // Funkcja animacji licznika
    function animateCounter(element, start, end, duration) {
        let startTime = null;
        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const currentNumber = Math.floor(progress * (end - start) + start);
            element.textContent = currentNumber.toLocaleString();
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = end.toLocaleString();
            }
        };
        requestAnimationFrame(step);
    }

    // Efekt parallax dla zdjęć historycznych
    const historyImages = document.querySelectorAll('.history-image');
    window.addEventListener('scroll', () => {
        historyImages.forEach(image => {
            const rect = image.getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight && rect.bottom >= 0;
            
            if (isVisible) {
                const speed = 0.15;
                const yPos = -(window.pageYOffset * speed);
                image.style.transform = `translateY(${yPos}px)`;
            }
        });
    });

    // Animacja osi czasu
    const timelineItems = document.querySelectorAll('.timeline-item');
    const timelineObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate-in');
                }, index * 200);
                timelineObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    timelineItems.forEach(item => {
        timelineObserver.observe(item);
    });

    // Galeria certyfikatów
    const certificateCards = document.querySelectorAll('.certificate-card');
    certificateCards.forEach(card => {
        card.addEventListener('click', () => {
            const img = card.querySelector('img');
            const modal = document.createElement('div');
            modal.className = 'certificate-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <img src="${img.src}" alt="${img.alt}">
                    <button class="modal-close">&times;</button>
                </div>
            `;
            document.body.appendChild(modal);
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.className === 'modal-close') {
                    modal.remove();
                }
            });
        });
    });
}); 