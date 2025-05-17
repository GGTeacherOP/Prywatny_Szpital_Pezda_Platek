document.addEventListener('DOMContentLoaded', () => {
    console.log('personel.js loaded');

    // Elementy DOM
    const staffCards = document.querySelectorAll('.staff-card');
    const searchInput = document.createElement('input');
    const filterButtons = document.createElement('div');
    const categories = ['Wszyscy', 'Lekarze', 'Pielęgniarki i Pielęgniarze', 'Personel Administracyjny i Rejestracja', 'Personel Pomocniczy'];

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
    personelSection.insertBefore(searchInput, personelSection.firstChild);
    personelSection.insertBefore(filterButtons, searchInput.nextSibling);

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