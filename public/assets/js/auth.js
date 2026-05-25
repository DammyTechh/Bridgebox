(() => {
    const openButtons = document.querySelectorAll('[data-modal-open]');
    const closeButtons = document.querySelectorAll('[data-modal-close]');

    const openModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
    };

    const closeModal = (id) => {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-open');
    };

    openButtons.forEach((button) => {
        button.addEventListener('click', () => openModal(button.dataset.modalOpen));
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', () => closeModal(button.dataset.modalClose));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        document.querySelectorAll('.modal.is-open').forEach((modal) => {
            modal.setAttribute('aria-hidden', 'true');
            modal.classList.remove('is-open');
        });
    });

    document.querySelectorAll('.modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('is-open');
            }
        });
    });
})();
