import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

// Import Chart.js and Stimulus controllers for UX Chartjs
import 'chart.js';
import '@symfony/ux-chartjs';
