import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via Webpack Encore.
 */
import './styles/app.css';
import countdown from 'countdown';

// Make countdown available globally for Twig templates
window.countdown = countdown;

console.log('Assets loaded via Webpack Encore 🎉');
