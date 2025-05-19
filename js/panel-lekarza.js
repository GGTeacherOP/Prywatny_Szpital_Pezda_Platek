document.addEventListener('DOMContentLoaded', function() {
    // Pobierz wszystkie linki z menu lekarza
    const menuLinks = document.querySelectorAll('.doctor-nav a');
    
    // Pobierz wszystkie sekcje
    const sections = {
        'panel-glowny': document.getElementById('panel-glowny'),
        'pacjenci': document.getElementById('pacjenci'),
        'wizyty': document.getElementById('wizyty'),
        'statystyki': document.getElementById('statystyki')
    };

    // Funkcja do zmiany widoczności sekcji
    function showSection(sectionId) {
        // Ukryj wszystkie sekcje
        Object.values(sections).forEach(section => {
            if (section) {
                section.style.display = 'none';
            }
        });

        // Pokaż wybraną sekcję
        if (sections[sectionId]) {
            sections[sectionId].style.display = 'block';
        }

        // Aktualizuj klasę active w menu
        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(sectionId)) {
                link.classList.add('active');
            }
        });
    }

    // Dodaj obsługę kliknięć do linków menu
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').replace('.php', '');
            showSection(sectionId);
            
            // Aktualizuj URL bez przeładowania strony
            history.pushState(null, '', this.getAttribute('href'));
        });
    });

    // Obsługa przycisków wstecz/dalej w przeglądarce
    window.addEventListener('popstate', function() {
        const currentPath = window.location.pathname;
        const sectionId = currentPath.split('/').pop().replace('.php', '');
        showSection(sectionId);
    });

    // Sprawdź URL przy załadowaniu strony
    const currentPath = window.location.pathname;
    const sectionId = currentPath.split('/').pop().replace('.php', '');
    if (sectionId && sections[sectionId]) {
        showSection(sectionId);
    } else {
        // Domyślnie pokaż panel główny
        showSection('panel-glowny');
    }
}); 