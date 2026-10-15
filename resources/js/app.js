import Alpine from 'alpinejs';
import DataTable from 'datatables.net-dt';
import { setupAuthNavigation } from './auth-navigation';
import { setupAppointmentNavigation } from './appointment-navigation';
import { setupDataTables } from './data-tables';
import { setupProfilePhotoUpload } from './profile-photo';
import { setupAppointmentAvailability } from './appointment-availability';

setupAuthNavigation(window);
setupAppointmentNavigation(window, document);
setupDataTables(DataTable, window, document);
setupProfilePhotoUpload(window, document);
setupAppointmentAvailability(window, document);

window.Alpine = Alpine;
Alpine.start();
