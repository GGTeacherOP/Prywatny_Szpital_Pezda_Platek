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
}); 