document.addEventListener('DOMContentLoaded', function() {
    // Pobieranie wszystkich paneli
    const panels = document.querySelectorAll('.panel-content');
    
    // Pobieranie wszystkich linków w menu
    const menuLinks = document.querySelectorAll('.nav-item');

    // Funkcja do przełączania paneli
    function switchPanel(panelId) {
        // Ukryj wszystkie panele
        panels.forEach(panel => {
            panel.style.display = 'none';
        });

        // Pokaż wybrany panel
        const selectedPanel = document.getElementById(panelId);
        if (selectedPanel) {
            selectedPanel.style.display = 'block';
        }

        // Aktualizuj aktywne linki w menu
        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-panel') === panelId) {
                link.classList.add('active');
            }
        });
    }

    // Dodanie obsługi kliknięć do linków menu
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const panelId = this.getAttribute('data-panel');
            if (panelId) {
                switchPanel(panelId);
            }
        });
    });

    // Domyślnie pokaż panel główny
    switchPanel('panel-glowny');

    // Funkcje dla listy pacjentów
    const patientSearch = document.getElementById('patientSearch');
    const sortBy = document.getElementById('sortBy');
    const patientCards = document.querySelectorAll('.patient-card');

    if (patientSearch) {
        patientSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            if (searchTerm.length === 0) {
                // Jeśli pole wyszukiwania jest puste, pokaż wszystkie karty
                patientCards.forEach(card => {
                    card.style.display = 'flex';
                });
                return;
            }

            patientCards.forEach(card => {
                const patientName = card.querySelector('h3').textContent.toLowerCase();
                const patientPesel = card.dataset.pesel;
                
                // Sprawdź, czy imię, nazwisko lub PESEL zawiera szukaną frazę
                const nameMatch = patientName.includes(searchTerm);
                const peselMatch = patientPesel.includes(searchTerm);
                
                // Jeśli szukamy po imieniu lub nazwisku, sprawdź dokładniejsze dopasowanie
                if (nameMatch) {
                    const [imie, nazwisko] = patientName.split(' ');
                    const searchWords = searchTerm.split(' ');
                    
                    // Sprawdź, czy wszystkie słowa z wyszukiwania pasują do imienia lub nazwiska
                    const isGoodMatch = searchWords.every(word => 
                        imie.startsWith(word) || nazwisko.startsWith(word)
                    );
                    
                    card.style.display = isGoodMatch ? 'flex' : 'none';
                } else if (peselMatch) {
                    // Jeśli szukamy po PESEL, pokaż tylko dokładne dopasowanie
                    card.style.display = patientPesel.startsWith(searchTerm) ? 'flex' : 'none';
                } else {
                    card.style.display = 'none';
                }
            });

            // Dodaj efekt podświetlenia dla pasujących elementów
            const matchingCards = document.querySelectorAll('.patient-card[style="display: flex;"]');
            if (matchingCards.length > 0) {
                matchingCards.forEach(card => {
                    const patientName = card.querySelector('h3');
                    const text = patientName.textContent;
                    const searchTermRegex = new RegExp(searchTerm, 'gi');
                    patientName.innerHTML = text.replace(searchTermRegex, match => 
                        `<span class="highlight">${match}</span>`
                    );
                });
            }
        });

        // Czyszczenie podświetlenia przy opuszczeniu pola wyszukiwania
        patientSearch.addEventListener('blur', function() {
            const highlightedElements = document.querySelectorAll('.highlight');
            highlightedElements.forEach(element => {
                const text = element.textContent;
                element.parentNode.replaceChild(document.createTextNode(text), element);
            });
        });
    }

    if (sortBy) {
        sortBy.addEventListener('change', function() {
            const patientsList = document.querySelector('.patients-list');
            const cards = Array.from(patientCards);
            
            cards.sort((a, b) => {
                const aValue = a.querySelector('h3').textContent;
                const bValue = b.querySelector('h3').textContent;
                
                switch(this.value) {
                    case 'name':
                        return aValue.split(' ')[0].localeCompare(bValue.split(' ')[0]);
                    case 'surname':
                        return aValue.split(' ')[1].localeCompare(bValue.split(' ')[1]);
                    case 'lastVisit':
                        const aDate = new Date(a.querySelector('.patient-visits-info p').textContent.split(': ')[1]);
                        const bDate = new Date(b.querySelector('.patient-visits-info p').textContent.split(': ')[1]);
                        return bDate - aDate;
                    default:
                        return 0;
                }
            });
            
            cards.forEach(card => patientsList.appendChild(card));
        });
    }

    // Obsługa przycisku "Historia wizyt"
    document.querySelectorAll('.btn-view-history').forEach(button => {
        button.addEventListener('click', function() {
            const patientCard = this.closest('.patient-card');
            const patientId = this.dataset.patientId;
            const historySection = patientCard.querySelector('.visit-history-section');
            
            if (historySection) {
                historySection.remove();
                this.textContent = 'Historia wizyt';
                return;
            }
            
            this.textContent = 'Ładowanie...';
            
            fetch(`get_patient_visits.php?patient_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    this.textContent = 'Ukryj historię';
                    
                    const historySection = document.createElement('div');
                    historySection.className = 'visit-history-section';
                    historySection.innerHTML = `
                        <h4>Historia wizyt</h4>
                        <div class="visit-list">
                            ${data.length > 0 ? data.map(visit => `
                                <div class="visit-item">
                                    <div class="visit-date">${visit.data_wizyty}</div>
                                    <div class="visit-type">${visit.typ_wizyty}</div>
                                    <div class="visit-status ${visit.status}">${visit.status}</div>
                                    ${visit.opis ? `<div class="visit-description">${visit.opis}</div>` : ''}
                                    <div class="doctor-info">Lekarz: ${visit.lekarz_imie} ${visit.lekarz_nazwisko}</div>
                                </div>
                            `).join('') : '<div class="no-visits">Brak historii wizyt</div>'}
                        </div>
                    `;
                    
                    patientCard.appendChild(historySection);
                })
                .catch(error => {
                    console.error('Błąd podczas pobierania historii wizyt:', error);
                    this.textContent = 'Historia wizyt';
                });
        });
    });

    // Obsługa przycisku "Wyniki badań"
    document.querySelectorAll('.btn-view-results').forEach(button => {
        button.addEventListener('click', function() {
            const patientId = this.dataset.patientId;
            const resultsSection = document.getElementById(`results-${patientId}`);
            
            if (resultsSection) {
                resultsSection.remove();
                this.textContent = 'Wyniki badań';
                this.classList.remove('active');
                return;
            }

            this.textContent = 'Ładowanie...';
            
            fetch(`get_patient_results.php?patient_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Wystąpił błąd podczas pobierania wyników');
                    }

                    const results = data.results;
                    const resultsHtml = `
                        <div id="results-${patientId}" class="results-section">
                            <h4>Wyniki badań</h4>
                            ${results.length > 0 ? `
                                <div class="results-list">
                                    ${results.map(result => `
                                        <div class="result-item">
                                            <div class="result-date">Data wystawienia: ${result.data_wystawienia}</div>
                                            <div class="result-type">Typ badania: ${result.typ_badania}</div>
                                            <div class="result-pin">PIN: ${result.pin}</div>
                                            <div class="result-status ${result.status}">Status: ${result.status}</div>
                                            <div class="result-doctor">
                                                <strong>Lekarz:</strong> ${result.lekarz_nazwisko}
                                                ${result.lekarz_specjalizacja ? `<br><strong>Specjalizacja:</strong> ${result.lekarz_specjalizacja}` : ''}
                                            </div>
                                            ${result.plik_wyniku ? `
                                                <div class="result-file">
                                                    <a href="${result.plik_wyniku}" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i> Pobierz wynik
                                                    </a>
                                                </div>
                                            ` : ''}
                                        </div>
                                    `).join('')}
                                </div>
                            ` : `
                                <div class="no-results">Brak wyników badań dla tego pacjenta.</div>
                            `}
                        </div>
                    `;

                    this.closest('.patient-card').insertAdjacentHTML('afterend', resultsHtml);
                    this.textContent = 'Ukryj wyniki';
                    this.classList.add('active');
                })
                .catch(error => {
                    console.error('Błąd:', error);
                    alert(error.message);
                    this.textContent = 'Wyniki badań';
                });
        });
    });

    // Obsługa formularza wyników badań
    const resultsForm = document.getElementById('resultsForm');
    const fileInput = document.getElementById('testFile');
    const fileName = document.querySelector('.file-name');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
            } else {
                fileName.textContent = 'Nie wybrano pliku';
            }
        });
    }

    if (resultsForm) {
        resultsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Sprawdzenie rozmiaru pliku
            const file = formData.get('plik');
            if (file && file.size > 10 * 1024 * 1024) { // 10MB
                alert('Plik jest za duży. Maksymalny rozmiar to 10MB.');
                return;
            }

            // Wyłączenie przycisku podczas wysyłania
            const submitButton = this.querySelector('.btn-submit');
            submitButton.disabled = true;
            submitButton.textContent = 'Wysyłanie...';

            fetch('save_result.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Wynik został pomyślnie zapisany.');
                    this.reset();
                    fileName.textContent = 'Nie wybrano pliku';
                } else {
                    throw new Error(data.message || 'Wystąpił błąd podczas zapisywania wyniku');
                }
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert(error.message || 'Wystąpił błąd podczas wysyłania formularza.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Wystaw wynik';
            });
        });
    }
}); 