// Doctor cards hover effect
const doctorCards = document.querySelectorAll('.doctor-card');

doctorCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-10px)';
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0)';
    });
});

// Filter doctors by specialization
const createFilterButtons = () => {
    const specializations = new Set();
    doctorCards.forEach(card => {
        const specialization = card.querySelector('.specialization').textContent;
        specializations.add(specialization);
    });

    const filterContainer = document.createElement('div');
    filterContainer.className = 'filter-container';
    
    const allButton = document.createElement('button');
    allButton.textContent = 'Wszyscy';
    allButton.className = 'filter-button active';
    allButton.dataset.filter = 'all';
    filterContainer.appendChild(allButton);

    specializations.forEach(spec => {
        const button = document.createElement('button');
        button.textContent = spec;
        button.className = 'filter-button';
        button.dataset.filter = spec;
        filterContainer.appendChild(button);
    });

    const sectionHeader = document.querySelector('.section-header');
    sectionHeader.appendChild(filterContainer);

    // Add filter functionality
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            // Filter doctors
            doctorCards.forEach(card => {
                const cardSpec = card.querySelector('.specialization').textContent;
                if (filter === 'all' || cardSpec === filter) {
                    card.style.display = 'block';
                    setTimeout(() => card.style.opacity = '1', 50);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => card.style.display = 'none', 300);
                }
            });
        });
    });
};

// Initialize filters if we're on the about page
if (document.querySelector('.our-doctors')) {
    createFilterButtons();
}

// Add smooth reveal animation for values
const valuesList = document.querySelector('.values-list');
if (valuesList) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    valuesList.querySelectorAll('li').forEach(item => {
        observer.observe(item);
    });
}

// Add doctor details modal
const createDoctorModal = () => {
    const modal = document.createElement('div');
    modal.className = 'doctor-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <div class="modal-body"></div>
        </div>
    `;
    document.body.appendChild(modal);

    // Add click handlers for doctor cards
    doctorCards.forEach(card => {
        const detailsButton = card.querySelector('.btn-details');
        if (detailsButton) {
            detailsButton.addEventListener('click', (e) => {
                e.preventDefault();
                const doctorInfo = card.querySelector('.doctor-info').innerHTML;
                modal.querySelector('.modal-body').innerHTML = doctorInfo;
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }
    });

    // Close modal handlers
    const closeModal = () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    };

    modal.querySelector('.modal-close').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });
};

// Initialize modal if we're on the about page
if (document.querySelector('.our-doctors')) {
    createDoctorModal();
} 