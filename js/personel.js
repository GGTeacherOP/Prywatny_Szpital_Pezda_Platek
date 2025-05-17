document.addEventListener('DOMContentLoaded', () => {
    console.log('personel.js loaded');

    // Elementy DOM
    const staffCards = document.querySelectorAll('.staff-card');
    const searchInput = document.createElement('input');
    const filterButtons = document.createElement('div');
    const categories = ['Wszyscy', 'Lekarze', 'Pielęgniarki i Pielęgniarze', 'Personel Administracyjny i Rejestracja', 'Personel Pomocniczy'];

    // Dodanie przycisku trybu ciemnego
    const darkModeToggle = document.createElement('button');
    darkModeToggle.className = 'dark-mode-toggle';
    darkModeToggle.innerHTML = '🌙';
    document.body.appendChild(darkModeToggle);

    // Dodanie sekcji statystyk
    const statsSection = document.createElement('div');
    statsSection.className = 'staff-stats';
    statsSection.innerHTML = `
        <div class="stats-container">
            <div class="stat-item">
                <span class="stat-icon">👨‍⚕️</span>
                <span class="stat-number" data-value="6">0</span>
                <span class="stat-label">Lekarzy</span>
            </div>
            <div class="stat-item">
                <span class="stat-icon">👩‍⚕️</span>
                <span class="stat-number" data-value="5">0</span>
                <span class="stat-label">Pielęgniarek</span>
            </div>
            <div class="stat-item">
                <span class="stat-icon">💼</span>
                <span class="stat-number" data-value="4">0</span>
                <span class="stat-label">Administracji</span>
            </div>
            <div class="stat-item">
                <span class="stat-icon">🧹</span>
                <span class="stat-number" data-value="2">0</span>
                <span class="stat-label">Pomocniczego</span>
            </div>
        </div>
    `;

    // Dodanie przycisku specjalizacji dla lekarzy
    const specializations = ['Wszystkie', 'Kardiologia', 'Neurologia', 'Chirurgia Ogólna', 'Pediatria'];
    const specializationFilter = document.createElement('div');
    specializationFilter.className = 'specialization-filter';
    specializationFilter.style.display = 'none'; // Domyślnie ukryty

    specializations.forEach(spec => {
        const button = document.createElement('button');
        button.textContent = spec;
        button.className = 'spec-btn';
        button.dataset.specialization = spec;
        specializationFilter.appendChild(button);
    });

    // Konfiguracja wyszukiwarki
    searchInput.type = 'text';
    searchInput.placeholder = 'Wyszukaj personel...';
    searchInput.className = 'staff-search';
    
    // Konfiguracja przycisków filtrowania
    filterButtons.className = 'filter-buttons';
    categories.forEach(category => {
        const button = document.createElement('button');
        button.textContent = category;
        button.className = 'filter-btn';
        button.dataset.category = category;
        
        // Dodanie ikony do przycisku
        const icon = document.createElement('span');
        icon.className = 'filter-icon';
        if (category === 'Wszyscy') {
            icon.innerHTML = '👥';
        } else if (category === 'Lekarze') {
            icon.innerHTML = '👨‍⚕️';
        } else if (category === 'Pielęgniarki i Pielęgniarze') {
            icon.innerHTML = '👩‍⚕️';
        } else if (category === 'Personel Administracyjny i Rejestracja') {
            icon.innerHTML = '💼';
        } else if (category === 'Personel Pomocniczy') {
            icon.innerHTML = '🧹';
        }
        button.prepend(icon);
        filterButtons.appendChild(button);
    });

    // Dodanie elementów do strony
    const personelSection = document.querySelector('.personel-section');
    personelSection.insertBefore(statsSection, personelSection.firstChild);
    personelSection.insertBefore(searchInput, statsSection.nextSibling);
    personelSection.insertBefore(filterButtons, searchInput.nextSibling);
    personelSection.insertBefore(specializationFilter, filterButtons.nextSibling);

    // Rozszerzenie kart personelu
    staffCards.forEach(card => {
        // Dodanie przycisku rozwijania
        const expandBtn = document.createElement('button');
        expandBtn.className = 'expand-btn';
        expandBtn.innerHTML = '▼';
        card.appendChild(expandBtn);

        // Dodanie sekcji szczegółów
        const detailsSection = document.createElement('div');
        detailsSection.className = 'staff-details';
        detailsSection.innerHTML = `
            <div class="staff-gallery">
                <div class="gallery-main">
                    <img src="${card.querySelector('img').src}" alt="Zdjęcie główne">
                </div>
                <div class="gallery-thumbnails">
                    <img src="${card.querySelector('img').src}" alt="Miniatura 1" class="active">
                    <img src="${card.querySelector('img').src}" alt="Miniatura 2">
                    <img src="${card.querySelector('img').src}" alt="Miniatura 3">
                </div>
            </div>
            <div class="staff-info">
                <h4>Szczegóły</h4>
                <p>${card.querySelector('.details').textContent}</p>
                <div class="staff-contact">
                    <p>📧 email@szpital.pl</p>
                    <p>📞 +48 123 456 789</p>
                </div>
            </div>
        `;
        card.appendChild(detailsSection);

        // Obsługa rozwijania
        expandBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            card.classList.toggle('expanded');
            expandBtn.innerHTML = card.classList.contains('expanded') ? '▲' : '▼';
        });

        // Obsługa galerii
        const thumbnails = detailsSection.querySelectorAll('.gallery-thumbnails img');
        const mainImage = detailsSection.querySelector('.gallery-main img');

        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', () => {
                thumbnails.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                mainImage.src = thumb.src;
                mainImage.style.animation = 'fadeIn 0.3s ease';
            });
        });
    });

    // Funkcja wyszukiwania z debounce
    let searchTimeout;
    function searchStaff(query) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = query.toLowerCase();
            let hasResults = false;

            staffCards.forEach(card => {
                const name = card.querySelector('h3').textContent.toLowerCase();
                const specialty = card.querySelector('.position-specialty').textContent.toLowerCase();
                const details = card.querySelector('.details').textContent.toLowerCase();
                
                const isVisible = name.includes(searchTerm) || 
                                specialty.includes(searchTerm) || 
                                details.includes(searchTerm);
                
                if (isVisible) {
                    hasResults = true;
                    card.style.display = 'block';
                    card.classList.add('search-match');
                    setTimeout(() => card.classList.remove('search-match'), 1000);
                } else {
                    card.style.display = 'none';
                }
            });

            // Animacja dla braku wyników
            const noResults = document.querySelector('.no-results');
            if (!hasResults) {
                if (!noResults) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-results';
                    noResultsDiv.innerHTML = `
                        <span class="no-results-icon">🔍</span>
                        <p>Nie znaleziono wyników dla "${query}"</p>
                    `;
                    personelSection.appendChild(noResultsDiv);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }, 300);
    }

    // Funkcja filtrowania z animacją
    function filterStaff(category) {
        const cards = Array.from(staffCards);
        cards.forEach((card, index) => {
            const cardCategory = card.closest('.personel-category').querySelector('h2').textContent;
            const isVisible = category === 'Wszyscy' || cardCategory === category;
            
            if (isVisible) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.classList.add('filter-match');
                    setTimeout(() => card.classList.remove('filter-match'), 1000);
                }, index * 100);
            } else {
                card.style.display = 'none';
            }
        });

        // Pokaż/ukryj filtr specjalizacji
        specializationFilter.style.display = category === 'Lekarze' ? 'flex' : 'none';
    }

    // Funkcja filtrowania po specjalizacji
    function filterBySpecialization(specialization) {
        const cards = Array.from(staffCards);
        cards.forEach(card => {
            const specialty = card.querySelector('.position-specialty').textContent;
            const isVisible = specialization === 'Wszystkie' || specialty.includes(specialization);
            card.style.display = isVisible ? 'block' : 'none';
        });
    }

    // Obsługa wyszukiwarki
    searchInput.addEventListener('input', (e) => {
        searchStaff(e.target.value);
    });

    // Obsługa przycisków filtrowania
    filterButtons.addEventListener('click', (e) => {
        if (e.target.classList.contains('filter-btn')) {
            // Animacja przycisku
            e.target.classList.add('btn-click');
            setTimeout(() => e.target.classList.remove('btn-click'), 300);

            // Usuń aktywną klasę ze wszystkich przycisków
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            // Dodaj aktywną klasę do klikniętego przycisku
            e.target.classList.add('active');
            // Filtruj personel
            filterStaff(e.target.dataset.category);
        }
    });

    // Obsługa przycisków specjalizacji
    specializationFilter.addEventListener('click', (e) => {
        if (e.target.classList.contains('spec-btn')) {
            document.querySelectorAll('.spec-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            e.target.classList.add('active');
            filterBySpecialization(e.target.dataset.specialization);
        }
    });

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

    // Funkcja animacji liczników
    function animateCounter(element, start, end, duration) {
        let startTime = null;
        const step = (timestamp) => {
            if (!startTime) startTime = timestamp;
            const progress = Math.min((timestamp - startTime) / duration, 1);
            const currentNumber = Math.floor(progress * (end - start) + start);
            element.textContent = currentNumber;
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = end;
            }
        };
        requestAnimationFrame(step);
    }

    // Obsługa trybu ciemnego
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        darkModeToggle.innerHTML = document.body.classList.contains('dark-mode') ? '☀️' : '🌙';
    });

    // Animacja statystyk przy przewijaniu
    const observerStats = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counters = entry.target.querySelectorAll('.stat-number');
                counters.forEach(counter => {
                    const targetValue = parseInt(counter.dataset.value);
                    animateCounter(counter, 0, targetValue, 2000);
                });
                observerStats.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    observerStats.observe(statsSection);
}); 