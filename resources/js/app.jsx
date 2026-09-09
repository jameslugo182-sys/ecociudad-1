import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { listenForLogout } from './sessionSync';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function SessionSynchronizer({ children }) {
    useEffect(
        () => listenForLogout(() => window.location.replace(route('login'))),
        [],
    );

    return children;
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <SessionSynchronizer>
                <App {...props} />
            </SessionSynchronizer>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
