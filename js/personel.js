document.addEventListener('DOMContentLoaded', () => {
    console.log('personel.js loaded');

    // Elementy DOM
    const staffCards = document.querySelectorAll('.staff-card');
    const filterButtons = document.createElement('div');

    // Tworzenie sekcji filtrowania
    const filterSection = document.createElement('div');
    filterSection.className = 'filter-buttons';
    filterSection.innerHTML = `
        <button class="filter-btn active" data-category="Wszyscy">Wszyscy</button>
        <button class="filter-btn" data-category="Lekarze">Lekarze</button>
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

    // Dodanie elementów do strony
    const personelSection = document.querySelector('.personel-section');
    personelSection.insertBefore(filterSection, personelSection.firstChild);
    filterSection.appendChild(specializationFilter);

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

    // Obsługa przycisków filtrowania
    filterSection.addEventListener('click', (e) => {
        if (e.target.classList.contains('filter-btn')) {
            // Animacja przycisku
            e.target.classList.add('btn-click');
            setTimeout(() => e.target.classList.remove('btn-click'), 300);

            // Usuń aktywną klasę ze wszystkich przycisków
            filterSection.querySelectorAll('.filter-btn').forEach(btn => {
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

    loadDoctors();
});

function loadDoctors() {
    const doctorsGrid = document.querySelector('.personel-category:first-child .staff-grid');
    if (!doctorsGrid) {
        console.error('Nie znaleziono kontenera na karty lekarzy');
        return;
    }

    // Pokaż stan ładowania
    doctorsGrid.innerHTML = '<div class="loading">Ładowanie danych lekarzy...</div>';

    fetch('get_doctors.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Otrzymano dane:', data);

            if (!data.success) {
                throw new Error(data.message || 'Wystąpił błąd podczas pobierania danych');
            }

            // Wyczyść kontener
            doctorsGrid.innerHTML = '';

            if (data.count === 0) {
                doctorsGrid.innerHTML = '<p class="info">Brak dostępnych lekarzy w bazie danych.</p>';
                return;
            }

            // Grupowanie lekarzy według specjalizacji
            const doctorsBySpecialization = {};
            data.doctors.forEach(doctor => {
                if (!doctorsBySpecialization[doctor.specjalizacja]) {
                    doctorsBySpecialization[doctor.specjalizacja] = [];
                }
                doctorsBySpecialization[doctor.specjalizacja].push(doctor);
            });

            // Tworzenie kart dla każdego lekarza
            Object.entries(doctorsBySpecialization).forEach(([specialization, doctors]) => {
                doctors.forEach(doctor => {
                    const card = createDoctorCard(doctor);
                    doctorsGrid.appendChild(card);
                });
            });
        })
        .catch(error => {
            console.error('Błąd podczas ładowania lekarzy:', error);
            doctorsGrid.innerHTML = `
                <div class="error">
                    <p>Nie udało się załadować danych lekarzy.</p>
                    <p>Szczegóły błędu: ${error.message}</p>
                    <button onclick="loadDoctors()" class="retry-button">Spróbuj ponownie</button>
                </div>
            `;
        });
}

function createDoctorCard(doctor) {
    const card = document.createElement('div');
    card.className = 'staff-card';

    const title = doctor.tytul_naukowy ? `${doctor.tytul_naukowy} ` : '';
    const fullName = `${title}${doctor.imie} ${doctor.nazwisko}`;

    // Sprawdź czy zdjęcie jest poprawnym URL lub base64
    let imageSrc = doctor.zdjecie;
    if (!imageSrc.startsWith('data:image/') && !imageSrc.startsWith('http') && !imageSrc.startsWith('/')) {
        console.warn('Nieprawidłowy format zdjęcia dla lekarza:', fullName);
        imageSrc = 'img/about-us/placeholder-user.png';
    }

    card.innerHTML = `
        <img src="${imageSrc}" alt="${fullName}" onerror="this.src='img/about-us/placeholder-user.png'">
        <h3>${fullName}</h3>
        <p class="position-specialty">${doctor.specjalizacja}</p>
        <p class="details">${doctor.opis || 'Brak opisu.'}</p>
    `;

    return card;
} 