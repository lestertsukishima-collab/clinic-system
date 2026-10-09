import Alpine from 'alpinejs';
import { setupAppointmentNavigation } from './appointment-navigation';

setupAppointmentNavigation(window, document);

window.Alpine = Alpine;
Alpine.start();
