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

    // Na początku ukryj wszystkie sekcje oprócz panelu głównego
    for (let key in sections) {
        if (sections[key]) {
            if (key === 'panel-glowny') {
                sections[key].style.display = 'block';
            } else {
                sections[key].style.display = 'none';
            }
        }
    }

    // Funkcja do pokazywania sekcji
    function showSection(sectionId) {
        // Ukryj wszystkie sekcje
        for (let key in sections) {
            if (sections[key]) {
                sections[key].style.display = 'none';
            }
        }

        // Pokaż wybraną sekcję
        if (sections[sectionId]) {
            sections[sectionId].style.display = 'block';
        }

        // Aktualizuj klasę active w menu
        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + sectionId) {
                link.classList.add('active');
            }
        });
    }

    // Dodaj obsługę kliknięć do linków menu
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').substring(1); // Usuń # z początku
            showSection(sectionId);
        });
    });
}); 