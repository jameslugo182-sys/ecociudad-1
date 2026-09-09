const LOGOUT_STORAGE_KEY = 'ecociudad:logout';

export function broadcastLogout() {
    window.localStorage.setItem(LOGOUT_STORAGE_KEY, Date.now().toString());
}

export function listenForLogout(callback) {
    const handleStorage = (event) => {
        if (event.key === LOGOUT_STORAGE_KEY && event.newValue) {
            callback();
        }
    };

    window.addEventListener('storage', handleStorage);

    return () => window.removeEventListener('storage', handleStorage);
}
