document.addEventListener('DOMContentLoaded', function() {
    // Obsługa nawigacji
    const navLinks = document.querySelectorAll('.patient-nav a');
    const sections = {
        'panel-glowny': document.getElementById('panel-glowny'),
        'historia-wizyt': document.getElementById('historia-wizyt'),
        'historia-wynikow': document.getElementById('historia-wynikow'),
        'wystaw-opinie': document.getElementById('wystaw-opinie')
    };

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Usuń klasę active ze wszystkich linków
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Dodaj klasę active do klikniętego linku
            this.classList.add('active');
            
            // Ukryj wszystkie sekcje
            Object.values(sections).forEach(section => {
                if (section) {
                    section.style.display = 'none';
                }
            });
            
            // Pokaż wybraną sekcję
            const targetId = this.getAttribute('href').substring(1);
            if (sections[targetId]) {
                sections[targetId].style.display = 'block';
            }
        });
    });

    // Obsługa formularza opinii
    const opinionForm = document.getElementById('opinionForm');
    if (opinionForm) {
        opinionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('save_opinion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Dziękujemy za wystawienie opinii!');
                    this.reset();
                } else {
                    alert('Wystąpił błąd: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas wysyłania opinii.');
            });
        });
    }

    // Obsługa oceny gwiazdkowej
    const ratingInputs = document.querySelectorAll('.rating input');
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const rating = this.value;
            console.log('Wybrana ocena:', rating);
        });
    });
}); 