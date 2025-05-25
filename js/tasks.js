document.addEventListener('DOMContentLoaded', function() {
    // Pobieranie danych o pracownikach i zadaniach
    fetchTasksData();

    // Obsługa formularza dodawania zadania
    const taskForm = document.querySelector('.tasks-form');
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addNewTask();
        });
    }
});

// Funkcja pobierająca dane o pracownikach i zadaniach
function fetchTasksData() {
    fetch('php/tasks.php?ajax=1')
        .then(response => response.json())
        .then(data => {
            populateStaffSelect(data.staff);
            displayTasks(data.tasks);
        })
        .catch(error => console.error('Błąd podczas pobierania danych:', error));
}

// Funkcja wypełniająca select pracownikami
function populateStaffSelect(staff) {
    const staffSelect = document.getElementById('staff-member');
    if (!staffSelect) return;

    staffSelect.innerHTML = '<option value="">Wybierz pracownika</option>';
    staff.forEach(member => {
        const option = document.createElement('option');
        option.value = member.id;
        option.textContent = `${member.imie} ${member.nazwisko} (${member.stanowisko})`;
        staffSelect.appendChild(option);
    });
}

// Funkcja wyświetlająca listę zadań
function displayTasks(tasks) {
    const tasksContainer = document.querySelector('.tasks-container');
    if (!tasksContainer) return;

    tasksContainer.innerHTML = '';
    tasks.forEach(task => {
        const taskCard = createTaskCard(task);
        tasksContainer.appendChild(taskCard);
    });
}

// Funkcja tworząca kartę zadania
function createTaskCard(task) {
    const card = document.createElement('div');
    card.className = 'task-card';
    
    const statusClass = task.status.replace(' ', '_');
    const statusText = {
        'do_wykonania': 'Do wykonania',
        'w_trakcie': 'W trakcie',
        'wykonane': 'Wykonane',
        'anulowane': 'Anulowane'
    }[task.status];

    card.innerHTML = `
        <h4>${task.typ_zadania === 'sprzatanie' ? 'Sprzątanie' : 'Konserwacja'} - ${task.numer_pomieszczenia}</h4>
        <div class="task-info">
            <p><strong>Pracownik:</strong> ${task.imie} ${task.nazwisko}</p>
            <p><strong>Data:</strong> ${formatDate(task.data_zadania)}</p>
            <p><strong>Godziny:</strong> ${task.godzina_rozpoczecia} - ${task.godzina_zakonczenia}</p>
            <p><strong>Opis:</strong> ${task.opis_zadania}</p>
        </div>
        <span class="task-status ${statusClass}">${statusText}</span>
        <div class="task-actions">
            ${task.status === 'do_wykonania' ? `
                <button onclick="updateTaskStatus(${task.id}, 'w_trakcie')" class="btn-update-status">
                    Rozpocznij
                </button>
            ` : ''}
            ${task.status === 'w_trakcie' ? `
                <button onclick="updateTaskStatus(${task.id}, 'wykonane')" class="btn-update-status">
                    Zakończ
                </button>
            ` : ''}
            ${task.status !== 'anulowane' && task.status !== 'wykonane' ? `
                <button onclick="updateTaskStatus(${task.id}, 'anulowane')" class="btn-cancel">
                    Anuluj
                </button>
            ` : ''}
        </div>
    `;
    
    return card;
}

// Funkcja dodająca nowe zadanie
function addNewTask() {
    const formData = new FormData();
    formData.append('action', 'add_task');
    formData.append('pracownik_id', document.getElementById('staff-member').value);
    formData.append('numer_pomieszczenia', document.getElementById('room-number').value);
    formData.append('typ_zadania', document.getElementById('task-type').value);
    formData.append('opis_zadania', document.getElementById('task-description').value);
    formData.append('data_zadania', document.getElementById('task-date').value);
    formData.append('godzina_rozpoczecia', document.getElementById('start-time').value);
    formData.append('godzina_zakonczenia', document.getElementById('end-time').value);

    fetch('php/tasks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            document.querySelector('.tasks-form').reset();
            fetchTasksData();
        } else {
            alert('Błąd: ' + data.message);
        }
    })
    .catch(error => console.error('Błąd podczas dodawania zadania:', error));
}

// Funkcja aktualizująca status zadania
function updateTaskStatus(taskId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('task_id', taskId);
    formData.append('status', newStatus);

    fetch('php/tasks.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchTasksData();
        } else {
            alert('Błąd: ' + data.message);
        }
    })
    .catch(error => console.error('Błąd podczas aktualizacji statusu:', error));
}

// Funkcja formatująca datę
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('pl-PL', options);
} 