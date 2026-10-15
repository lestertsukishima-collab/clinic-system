import Alpine from 'alpinejs';
import DataTable from 'datatables.net-dt';
import { setupAuthNavigation } from './auth-navigation';
import { setupAppointmentNavigation } from './appointment-navigation';
import { setupDataTables } from './data-tables';
import { setupProfilePhotoUpload } from './profile-photo';

setupAuthNavigation(window);
setupAppointmentNavigation(window, document);
setupDataTables(DataTable, window, document);
setupProfilePhotoUpload(window, document);

window.Alpine = Alpine;
Alpine.start();
