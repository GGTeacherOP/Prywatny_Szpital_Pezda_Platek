document.addEventListener('DOMContentLoaded', () => {
    // Funkcja do animacji licznika
    function animateCounter(element, target) {
        // Ustawiamy początkową wartość na 0
        element.textContent = '0';
        
        // Używamy requestAnimationFrame dla płynnej animacji
        let startTime = null;
        const duration = 2000; // 2 sekundy
        
        function updateCounter(timestamp) {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            
            // Używamy funkcji easeOutQuad dla płynniejszej animacji
            const easeProgress = 1 - Math.pow(1 - progress, 2);
            const currentValue = Math.floor(easeProgress * target);
            
            element.textContent = currentValue;
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        }
        
        requestAnimationFrame(updateCounter);
    }

    // Pobieranie danych z serwera
    fetch('get_stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Błąd sieci: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Otrzymane dane:', data);

            // Aktualizacja liczników
            const satisfiedPatientsElement = document.getElementById('satisfied-patients');
            const totalDoctorsElement = document.getElementById('total-doctors');
            const totalDepartmentsElement = document.getElementById('total-departments');
            const yearsExperienceElement = document.getElementById('years-experience');

            // Ustawiamy wszystkie elementy na 0 i dodajemy klasę dla animacji
            [satisfiedPatientsElement, totalDoctorsElement, totalDepartmentsElement, yearsExperienceElement].forEach(el => {
                if (el) {
                    el.textContent = '0';
                    el.style.opacity = '0';
                }
            });

            // Po krótkim opóźnieniu rozpoczynamy animacje
            setTimeout(() => {
                if (satisfiedPatientsElement && typeof data.satisfiedPatients === 'number') {
                    satisfiedPatientsElement.style.opacity = '1';
                    animateCounter(satisfiedPatientsElement, data.satisfiedPatients);
                }

                if (totalDoctorsElement && typeof data.totalDoctors === 'number') {
                    totalDoctorsElement.style.opacity = '1';
                    animateCounter(totalDoctorsElement, data.totalDoctors);
                }

                if (totalDepartmentsElement && typeof data.totalDepartments === 'number') {
                    totalDepartmentsElement.style.opacity = '1';
                    animateCounter(totalDepartmentsElement, data.totalDepartments);
                }

                if (yearsExperienceElement) {
                    const yearsValue = parseInt(yearsExperienceElement.dataset.value) || 30;
                    yearsExperienceElement.style.opacity = '1';
                    animateCounter(yearsExperienceElement, yearsValue);
                }
            }, 100);
        })
        .catch(error => {
            console.error('Błąd podczas pobierania danych:', error);
        });

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