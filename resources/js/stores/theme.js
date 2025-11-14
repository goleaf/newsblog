/**
 * Theme Store
 * Manages dark mode theme state and persistence
 */
export default () => ({
    theme: localStorage.getItem('theme') || 'system',
    
    init() {
        this.applyTheme();
        
        // Watch for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)')
            .addEventListener('change', () => {
                if (this.theme === 'system') {
                    this.applyTheme();
                }
            });
    },
    
    toggle() {
        const themes = ['light', 'dark', 'system'];
        const currentIndex = themes.indexOf(this.theme);
        this.theme = themes[(currentIndex + 1) % themes.length];
        localStorage.setItem('theme', this.theme);
        this.applyTheme();
    },
    
    setTheme(theme) {
        if (['light', 'dark', 'system'].includes(theme)) {
            this.theme = theme;
            localStorage.setItem('theme', this.theme);
            this.applyTheme();
        }
    },
    
    applyTheme() {
        const isDark = this.theme === 'dark' || 
            (this.theme === 'system' && 
             window.matchMedia('(prefers-color-scheme: dark)').matches);
        
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    
    get isDark() {
        return document.documentElement.classList.contains('dark');
    },
    
    get currentTheme() {
        return this.theme;
    }
});
