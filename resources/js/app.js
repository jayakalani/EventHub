import './bootstrap';
import { bindTitleCaseFields } from './title-case';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    bindTitleCaseFields();
});
