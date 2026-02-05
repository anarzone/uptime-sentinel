import { registerVueControllerComponents } from '@symfony/ux-vue';
import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import LandingPage from './vue/controllers/LandingPage.js';
import MonitorManager from './vue/controllers/MonitorManager.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

registerVueControllerComponents({ LandingPage, MonitorManager });