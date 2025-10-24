import './bootstrap';
import Echo from 'laravel-echo';


window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: false,
    disableStats: true,
});

window.Echo.channel('ad-script-tasks')
    .listen('.task.updated', (e) => {
        // Tell Livewire to refresh
        window.Livewire.dispatch('task-updated');
    });
