// Bootstrap JS
//
// This project uses Vite, which builds ES modules - `require()` is not
// available in the browser. Import libraries as ES modules instead.
//
// Livewire is loaded via @livewireScripts in the layout, so no HTTP client is
// needed here yet. If the POS frontend later needs XHR, add axios as a
// dependency (`npm i axios`) and use:
//     import axios from 'axios';
//     window.axios = axios;