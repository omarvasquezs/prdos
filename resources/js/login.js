import './bootstrap';
import { createApp } from 'vue';
import LoginForm from './components/LoginForm.vue';

const app = createApp(LoginForm);

if (document.getElementById('login')) {
    app.mount('#login');
}
