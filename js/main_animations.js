console.log('main_animations.js loaded');

document.addEventListener('DOMContentLoaded', () => {
    console.log('main_animations.js DOMContentLoaded');

    // 1. Animacje przy przewijaniu (Scroll Animations)
    const animatedSections = document.querySelectorAll('.animated-section');

    if (animatedSections.length > 0) {
        const sectionObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    // Opcjonalnie: przestań obserwować po pierwszym pojawieniu się
                    // observer.unobserve(entry.target);
                }
            });
        }, {
            root: null, // względem viewportu
            rootMargin: '0px',
            threshold: 0.1 // % widoczności elementu, aby uruchomić callback
        });

        animatedSections.forEach(section => {
            sectionObserver.observe(section);
        });
    } else {
        console.log('No animated sections found.');
    }

    // 3. Slider/karuzela dla opinii
    const reviewsContainer = document.querySelector('.reviews-container');
    const prevButton = document.querySelector('.slider-btn.prev-btn');
    const nextButton = document.querySelector('.slider-btn.next-btn');

    if (reviewsContainer && prevButton && nextButton) {
        const slides = Array.from(reviewsContainer.querySelectorAll('.review-card'));
        let currentSlide = 0;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active-slide');
            });
            slides[index].classList.add('active-slide');
            reviewsContainer.dataset.currentSlide = index; 
        }

        if (slides.length > 0) {
            showSlide(currentSlide);
        } else {
            if (document.querySelector('.slider-controls')) {
                document.querySelector('.slider-controls').style.display = 'none';
            }
        }
        
        prevButton.addEventListener('click', () => {
            currentSlide = (currentSlide > 0) ? currentSlide - 1 : slides.length - 1;
            showSlide(currentSlide);
        });

        nextButton.addEventListener('click', () => {
            currentSlide = (currentSlide < slides.length - 1) ? currentSlide + 1 : 0;
            showSlide(currentSlide);
        });

        if (slides.length <= 1) {
            if (document.querySelector('.slider-controls')) {
                 document.querySelector('.slider-controls').style.display = 'none';
            }
        }

    } else {
        console.log('Review slider elements not found.');
    }

    // Tu w przyszłości dodamy kod dla:
    // 2. Subtelnych efektów hover (jeśli JS będzie potrzebny - na razie CSS wystarcza)
    // 4. Efektu Parallax (został usunięty)
}); 