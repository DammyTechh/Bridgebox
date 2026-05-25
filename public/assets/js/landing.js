(() => {
    const body = document.body;
    if (!body) {
        return;
    }

    const setRoleScreen = () => {
        body.setAttribute('data-screen', 'role');
    };

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        setRoleScreen();
        return;
    }

    const delayMs = 2800;
    window.setTimeout(setRoleScreen, delayMs);

    const skipHandler = () => {
        if (body.getAttribute('data-screen') === 'splash') {
            setRoleScreen();
        }
    };

    window.addEventListener('click', skipHandler, { once: true });
    window.addEventListener('keydown', skipHandler, { once: true });
})();
