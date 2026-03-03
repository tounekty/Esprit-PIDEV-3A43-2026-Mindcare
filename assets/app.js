import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
<<<<<<< HEAD
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper!');

=======
 * This file will be included onto the page via Webpack Encore.
 */
import './styles/app.css';
import countdown from 'countdown';

// Make countdown available globally for Twig templates
window.countdown = countdown;

console.log('Assets loaded via Webpack Encore 🎉');
>>>>>>> origin/sara
