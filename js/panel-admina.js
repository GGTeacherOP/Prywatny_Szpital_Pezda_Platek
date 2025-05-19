document.addEventListener('DOMContentLoaded', function() {
    // Obsługa nawigacji
    const navLinks = document.querySelectorAll('.admin-nav a');
    const sections = document.querySelectorAll('.dashboard-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Usuń klasę active ze wszystkich linków
            navLinks.forEach(l => l.classList.remove('active'));
            // Dodaj klasę active do klikniętego linku
            this.classList.add('active');

            // Ukryj wszystkie sekcje
            sections.forEach(section => section.style.display = 'none');

            // Pokaż wybraną sekcję
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).style.display = 'block';
        });
    });

    // Obsługa formularza nowej wiadomości
    const newsForm = document.getElementById('newsForm');
    if (newsForm) {
        newsForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('save_news.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Wiadomość została zapisana pomyślnie!');
                    newsForm.reset();
                } else {
                    alert('Wystąpił błąd podczas zapisywania wiadomości: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas zapisywania wiadomości.');
            });
        });
    }

    // Obsługa zmiany statusu opinii
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const reviewId = this.dataset.reviewId;
            const newStatus = this.value;

            fetch('update_review_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    review_id: reviewId,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aktualizuj wygląd karty opinii
                    const reviewCard = this.closest('.review-history-card');
                    const statusElement = reviewCard.querySelector('.review-status');
                    if (statusElement) {
                        statusElement.className = 'review-status ' + newStatus;
                        statusElement.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                    }
                } else {
                    alert('Wystąpił błąd podczas aktualizacji statusu: ' + data.message);
                    // Przywróć poprzednią wartość
                    this.value = this.dataset.originalValue;
                }
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas aktualizacji statusu.');
                // Przywróć poprzednią wartość
                this.value = this.dataset.originalValue;
            });
        });

        // Zapisz oryginalną wartość przy załadowaniu
        select.dataset.originalValue = select.value;
    });
});

// Funkcja do usuwania wiadomości
function deleteNews(newsId) {
    if (confirm('Czy na pewno chcesz usunąć tę wiadomość?')) {
        fetch('delete_news.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                news_id: newsId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Usuń kartę wiadomości z interfejsu
                const newsCard = document.querySelector(`.news-history-card[data-news-id="${newsId}"]`);
                if (newsCard) {
                    newsCard.remove();
                }
                alert('Wiadomość została usunięta');
            } else {
                alert('Wystąpił błąd podczas usuwania wiadomości: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Błąd:', error);
            alert('Wystąpił błąd podczas usuwania wiadomości.');
        });
    }
} 