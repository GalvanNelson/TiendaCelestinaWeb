import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Si tu proyecto está en subcarpeta, ajusta la baseURL automáticamente
//window.axios.defaults.baseURL = '/inf513/grupo14sa/TiendaCelestinaWeb/public';

// Muy importante:
//window.axios.defaults.withCredentials = true;

// Token CSRF desde meta
//let token = document.head.querySelector('meta[name="csrf-token"]');

/*if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}*/

// *** Línea obligatoria para Inertia y login ***
//window.axios.get('/sanctum/csrf-cookie');
