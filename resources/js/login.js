import './bootstrap';
import { createApp } from 'vue';
import LoginForm from './components/LoginForm.vue';

// Configure CSRF token for axios
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

const app = createApp(LoginForm);

if (document.getElementById('login')) {
    app.mount('#login');
}
