(() => {
    const forms = document.querySelectorAll('form[data-admin-action]');
    if (!forms.length) {
        return;
    }

    const alertBox = document.getElementById('action-alert');
    const logBody = document.querySelector('[data-action-log-body]');
    const modal = document.getElementById('confirm-modal');
    const modalMessage = modal ? modal.querySelector('[data-confirm-message]') : null;
    const modalYes = modal ? modal.querySelector('[data-confirm-yes]') : null;
    const modalNo = modal ? modal.querySelector('[data-confirm-no]') : null;
    const modalClose = modal ? modal.querySelector('[data-confirm-close]') : null;

    let pendingResolve = null;

    const setAlert = (message, isSuccess) => {
        if (!alertBox) {
            return;
        }
        const alertMessage = alertBox.querySelector('[data-alert-message]');
        if (alertMessage) {
            alertMessage.textContent = message;
        } else {
            alertBox.textContent = message;
        }
        alertBox.hidden = !message;
        if (message) {
            alertBox.removeAttribute('hidden');
            alertBox.style.display = 'flex';
        }
        alertBox.classList.remove('alert-success', 'alert-error');
        if (message) {
            alertBox.classList.add(isSuccess ? 'alert-success' : 'alert-error');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            alertBox.dispatchEvent(new CustomEvent('alert:show'));
        }
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    const insertLogRow = (log) => {
        if (!logBody || !log) {
            return;
        }

        const emptyRow = logBody.querySelector('[data-action-log-empty]');
        if (emptyRow) {
            emptyRow.remove();
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(log.time || '')}</td>
            <td>${escapeHtml(log.user || 'Unknown')}</td>
            <td>${escapeHtml(log.action || '')}</td>
            <td>${escapeHtml(log.result || '')}</td>
            <td>${escapeHtml(log.message || '')}</td>
        `;

        logBody.prepend(row);
    };

    const clearLogRows = () => {
        if (!logBody) {
            return;
        }
        logBody.innerHTML = '';
        const empty = document.createElement('tr');
        empty.setAttribute('data-action-log-empty', 'true');
        empty.innerHTML = '<td colspan="5">No actions logged yet.</td>';
        logBody.appendChild(empty);
    };

    const setLoading = (form, isLoading) => {
        const buttons = Array.from(form.querySelectorAll('button[type="submit"]'));
        buttons.forEach((button) => {
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.textContent;
                button.dataset.initialDisabled = button.disabled ? 'true' : 'false';
            }
            button.disabled = isLoading ? true : button.dataset.initialDisabled === 'true';
            button.textContent = isLoading ? 'Working...' : button.dataset.originalText;
        });

        const toggleInput = form.querySelector('input[type="checkbox"]');
        if (toggleInput) {
            if (!toggleInput.dataset.initialDisabled) {
                toggleInput.dataset.initialDisabled = toggleInput.disabled ? 'true' : 'false';
            }
            toggleInput.dataset.locked = isLoading ? 'true' : 'false';
            toggleInput.disabled = isLoading ? true : toggleInput.dataset.initialDisabled === 'true';
        }
    };

    const closeModal = (result) => {
        if (!modal || !pendingResolve) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        const resolve = pendingResolve;
        pendingResolve = null;
        resolve(result);
    };

    const openModal = (message) => {
        if (!modal) {
            return Promise.resolve(window.confirm(message));
        }
        if (modalMessage) {
            modalMessage.textContent = message;
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        return new Promise((resolve) => {
            pendingResolve = resolve;
        });
    };

    const confirmAction = async (message) => {
        if (!message) {
            return true;
        }
        return openModal(message);
    };

    if (modalYes) {
        modalYes.addEventListener('click', () => closeModal(true));
    }

    if (modalNo) {
        modalNo.addEventListener('click', () => closeModal(false));
    }

    if (modalClose) {
        modalClose.addEventListener('click', () => closeModal(false));
    }

    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(false);
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal(false);
        }
    });

    const runAction = async ({ url, form, actionName, onComplete }) => {
        setAlert('', true);
        setLoading(form, true);

        const formData = new FormData(form);
        const csrfToken = formData.get('_token') || '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: formData,
            });

            if (response.status === 419) {
                setAlert('Session expired. Refreshing for a new token...', false);
                window.location.reload();
                return { success: false };
            }

            let payload = null;
            let rawText = '';
            try {
                payload = await response.json();
            } catch (error) {
                rawText = await response.text().catch(() => '');
            }

            const success = response.ok && payload?.success !== false;
            const message = payload?.message || rawText || (response.ok ? 'Action completed.' : `Action failed (${response.status}).`);

            setAlert(message, success);

            if ((payload?.logs_cleared || actionName === 'clear_logs') && success) {
                clearLogRows();
            }

            insertLogRow(payload?.log);

            if (onComplete) {
                onComplete(success, payload);
            }

            return { success, payload };
        } catch (error) {
            setAlert('Unable to reach the server. Check your connection.', false);
            if (onComplete) {
                onComplete(false, null);
            }
            return { success: false };
        } finally {
            setLoading(form, false);
        }
    };

    forms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const confirmMessage = form.dataset.confirm;
            const confirmed = await confirmAction(confirmMessage);
            if (!confirmed) {
                return;
            }

            await runAction({
                url: form.action,
                form,
                actionName: form.dataset.actionName,
            });
        });
    });

    const toggleForms = document.querySelectorAll('form[data-admin-toggle]');
    toggleForms.forEach((form) => {
        const toggleInput = form.querySelector('input[type="checkbox"]');
        if (!toggleInput) {
            return;
        }

        toggleInput.addEventListener('change', async () => {
            const nextState = toggleInput.checked;
            const url = nextState ? form.dataset.actionOn : form.dataset.actionOff;
            const confirmMessage = nextState ? form.dataset.confirmOn : form.dataset.confirmOff;
            const confirmed = await confirmAction(confirmMessage);

            if (!confirmed) {
                toggleInput.checked = !nextState;
                return;
            }

            await runAction({
                url,
                form,
                actionName: nextState ? form.dataset.actionOn : form.dataset.actionOff,
                onComplete: (success) => {
                    if (!success) {
                        toggleInput.checked = !nextState;
                    }
                },
            });
        });
    });
})();
