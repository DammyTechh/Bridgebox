(() => {
    const form = document.getElementById('login-form');
    if (!form) {
        return;
    }

    const errorBox = document.getElementById('login-error');
    const submitButton = form.querySelector('button[type="submit"]');

    const setError = (message) => {
        if (!errorBox) {
            return;
        }
        errorBox.textContent = message;
        errorBox.hidden = !message;
    };

    const setLoading = (isLoading) => {
        if (!submitButton) {
            return;
        }
        submitButton.disabled = isLoading;
        submitButton.textContent = isLoading ? 'Signing in…' : 'Login';
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setError('');
        setLoading(true);

        try {
            const formData = new FormData(form);
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                credentials: 'same-origin',
                body: formData,
            });

            if (response.ok) {
                const data = await response.json();
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                window.location.reload();
                return;
            }

            if (response.status === 419) {
                setError('Session expired. Refreshing for a new token...');
                window.location.reload();
                return;
            }

            const payload = await response.json().catch(() => null);
            const message = payload?.message || payload?.errors?.identifier?.[0] || 'Login failed. Please try again.';
            setError(message);
        } catch (error) {
            setError('Unable to reach the login service. Check your connection.');
        } finally {
            setLoading(false);
        }
    });
})();
