console.log('main_animations.js loaded');

document.addEventListener('DOMContentLoaded', () => {
    console.log('main_animations.js DOMContentLoaded');

    // 1. Animacje przy przewijaniu (Scroll Animations)
    const animatedSections = document.querySelectorAll('.animated-section');

    // Funkcja do animowania liczników
    function animateCounter(element, start, end, duration) {
        let startTime = null;
        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const currentNumber = Math.floor(progress * (end - start) + start);
            // Formatowanie liczby z separatorem tysięcy (jeśli oryginalnie go miała)
            if (element.dataset.originalValue.includes(',')) {
                element.textContent = currentNumber.toLocaleString('pl-PL');
            } else {
                element.textContent = currentNumber;
            }
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                // Upewniamy się, że na końcu jest dokładna wartość docelowa sformatowana
                 if (element.dataset.originalValue.includes(',')) {
                    element.textContent = parseInt(element.dataset.originalValue.replace(/[^0-9]/g, ''), 10).toLocaleString('pl-PL');
                } else {
                    element.textContent = element.dataset.originalValue;
                }
            }
        };
        requestAnimationFrame(step);
    }

    const observer = new IntersectionObserver((entries, observerInstance) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');

                // Sprawdzanie, czy to sekcja statystyk i uruchomienie animacji liczników
                if (entry.target.classList.contains('stats-section')) {
                    const counters = entry.target.querySelectorAll('.stat-number');
                    counters.forEach(counter => {
                        // Zapobiegaj ponownej animacji, jeśli już była
                        if (counter.dataset.animated) return;
                        counter.dataset.animated = true; 

                        const originalValue = counter.textContent;
                        counter.dataset.originalValue = originalValue; // Zapisujemy oryginalną wartość z formatowaniem
                        
                        // Usuwamy formatowanie (np. przecinki) i parsujemy do liczby
                        const targetValue = parseInt(originalValue.replace(/[^0-9]/g, ''), 10);
                        
                        if (!isNaN(targetValue)) {
                            // Rozpoczynamy animację od 0 lub mniejszej wartości dla efektu
                            animateCounter(counter, 0, targetValue, 2500); // Animacja przez 2.5 sekundy
                        }
                    });
                }
                // Można dodać tutaj odpinanie obserwatora dla tej sekcji, jeśli animacja ma być jednorazowa
                // observerInstance.unobserve(entry.target); 
            }
            // Opcjonalnie: usuń klasę, gdy element opuszcza viewport (dla ponownej animacji przy scrollu w górę i w dół)
            // else {
            //     entry.target.classList.remove('is-visible');
            //     // Resetowanie stanu animacji liczników, jeśli chcemy, aby animowały się za każdym razem
            //     if (entry.target.classList.contains('stats-section')) {
            //         const counters = entry.target.querySelectorAll('.stat-number');
            //         counters.forEach(counter => delete counter.dataset.animated);
            //     }
            // }
        });
    }, {
        root: null, // względem viewportu
        threshold: 0.1 // uruchom, gdy 10% elementu jest widoczne
    });

    animatedSections.forEach(section => {
        observer.observe(section);
    });

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

    // 5. Przycisk "Powrót na górę"
    const backToTopButton = document.getElementById("backToTopBtn");

    if (backToTopButton) {
        window.addEventListener("scroll", () => {
            if (window.pageYOffset > 300) { // Pokaż przycisk po przewinięciu o 300px
                backToTopButton.classList.add("show");
            } else {
                backToTopButton.classList.remove("show");
            }
        });

        backToTopButton.addEventListener("click", () => {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

}); 