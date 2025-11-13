@if(config('performance.critical_css.enabled') && file_exists(config('performance.critical_css.path')))
    @php
        $criticalCss = file_get_contents(config('performance.critical_css.path'));
        $maxSize = config('performance.critical_css.max_size', 14336);
        
        // Only inline if within size limit
        if (strlen($criticalCss) <= $maxSize) {
            echo '<style id="critical-css">' . $criticalCss . '</style>';
        }
    @endphp
@endif
