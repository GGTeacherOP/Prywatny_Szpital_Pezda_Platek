document.addEventListener('DOMContentLoaded', function() {
    // Obsługa przełączania paneli
    const navItems = document.querySelectorAll('.nav-item');
    const panels = document.querySelectorAll('.panel-content');

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Usuń klasę active ze wszystkich elementów nawigacji
            navItems.forEach(nav => nav.classList.remove('active'));
            
            // Dodaj klasę active do klikniętego elementu
            this.classList.add('active');
            
            // Ukryj wszystkie panele
            panels.forEach(panel => panel.style.display = 'none');
            
            // Pokaż wybrany panel
            const panelId = this.getAttribute('data-panel');
            document.getElementById(panelId).style.display = 'block';
        });
    });

    // Funkcja do wyświetlania błędu
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        const errorDiv = formGroup.querySelector('.error-message') || document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        if (!formGroup.querySelector('.error-message')) {
            formGroup.appendChild(errorDiv);
        }
        input.classList.add('error');
    }

    // Funkcja do usuwania błędu
    function clearError(input) {
        const formGroup = input.closest('.form-group');
        const errorDiv = formGroup.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.remove();
        }
        input.classList.remove('error');
    }

    // Obsługa formularza umawiania wizyty
    const visitForm = document.getElementById('visitForm');
    if (visitForm) {
        // Czyszczenie błędów przy zmianie wartości
        const inputs = visitForm.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearError(this);
            });
        });

        visitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Czyszczenie wszystkich poprzednich błędów
            const errorMessages = this.querySelectorAll('.error-message');
            errorMessages.forEach(error => error.remove());
            const errorInputs = this.querySelectorAll('.error');
            errorInputs.forEach(input => input.classList.remove('error'));
            
            // Walidacja formularza
            const requiredFields = ['lekarz_id', 'data_wizyty', 'godzina_wizyty', 'typ_wizyty'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = this.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    isValid = false;
                    showError(input, 'To pole jest wymagane');
                }
            });

            if (!isValid) {
                return;
            }

            const formData = new FormData(this);
            
            fetch('save_visit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Wizyta została umówiona pomyślnie');
                    this.reset();
                    window.location.reload();
                } else {
                    alert(data.message || 'Wystąpił błąd podczas umawiania wizyty');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas umawiania wizyty');
            });
        });
    }

    // Obsługa kliknięć w karty wyników
    const resultCards = document.querySelectorAll('.result-card');
    resultCards.forEach(card => {
        card.addEventListener('click', function() {
            const resultId = this.getAttribute('data-result-id');
            window.location.href = `wynik_szczegoly.php?id=${resultId}`;
        });
    });

    // Obsługa kliknięć w karty recept
    const prescriptionCards = document.querySelectorAll('.prescription-card');
    prescriptionCards.forEach(card => {
        card.addEventListener('click', function() {
            const prescriptionId = this.getAttribute('data-prescription-id');
            window.location.href = `recepta_szczegoly.php?id=${prescriptionId}`;
        });
    });
}); 