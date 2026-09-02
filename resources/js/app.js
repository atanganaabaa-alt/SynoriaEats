
import Alpine from 'alpinejs';
import { initTheme } from './theme';

window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', initTheme);

Alpine.start();
