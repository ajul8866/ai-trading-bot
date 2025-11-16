# Unused Livewire Components

The following Livewire components exist in the codebase but are **NOT currently referenced** in any views or routes. They may have been created for future features.

## Dashboard Components (Not Used)

1. **AdvancedMetrics** - `app/Livewire/Dashboard/AdvancedMetrics.php`
   - View: `resources/views/livewire/dashboard/advanced-metrics.blade.php`
   - Status: Not integrated into dashboard

2. **MarketScanner** - `app/Livewire/Dashboard/MarketScanner.php`
   - View: `resources/views/livewire/dashboard/market-scanner.blade.php`
   - Status: Not integrated into dashboard

3. **NewsPanel** - `app/Livewire/Dashboard/NewsPanel.php`
   - View: `resources/views/livewire/dashboard/news-panel.blade.php`
   - Status: Not integrated into dashboard

4. **OrderBook** - `app/Livewire/Dashboard/OrderBook.php`
   - View: `resources/views/livewire/dashboard/order-book.blade.php`
   - Status: Not integrated into dashboard

5. **MarketDepth** - `app/Livewire/Dashboard/MarketDepth.php`
   - View: `resources/views/livewire/dashboard/market-depth.blade.php`
   - Status: Not integrated into dashboard

6. **TradingPanel** - `app/Livewire/Dashboard/TradingPanel.php`
   - View: `resources/views/livewire/dashboard/trading-panel.blade.php`
   - Status: Not integrated into dashboard

7. **PerformanceMetrics** - `app/Livewire/Dashboard/PerformanceMetrics.php`
   - View: `resources/views/livewire/dashboard/performance-metrics.blade.php`
   - Status: Not integrated into dashboard

## Recommendations

1. **Delete if not needed**: Remove these files to reduce codebase size (~3,500 LOC)
2. **Integrate if useful**: Add `<livewire:dashboard.component-name />` to dashboard view
3. **Document if planned**: Update this file with implementation timeline

## Estimated Code Size

- Total unused PHP files: ~1,400 lines
- Total unused Blade templates: ~2,100 lines
- **Total unused code: ~3,500 lines**

## Last Audit

Date: 2025-11-16
Auditor: Comprehensive Codebase Audit
