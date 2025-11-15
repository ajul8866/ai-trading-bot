import './bootstrap';

// Import Alpine.js
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Register Alpine.js plugins
Alpine.plugin(collapse);

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();

// Import Lightweight Charts for trading charts
import { createChart } from 'lightweight-charts';
window.createChart = createChart;
