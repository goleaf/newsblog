/**
 * Accessibility JavaScript Module
 * 
 * Handles keyboard navigation, focus management, and other accessibility features
 */

class AccessibilityManager {
    constructor() {
        this.focusableElements = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
        this.shortcuts = {};
        this.init();
    }

    init() {
        this.setupKeyboardNavigation();
        this.setupFocusManagement();
        this.setupSkipLinks();
        this.setupAriaLiveRegions();
        this.setupReducedMotion();
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardNavigation() {
        // Register keyboard shortcuts
        this.registerShortcut('/', () => {
            const searchInput = document.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.focus();
                return true; // Prevent default
            }
        });

        this.registerShortcut('Escape', () => {
            // Close modals
            const openModal = document.querySelector('[role="dialog"][aria-hidden="false"]');
            if (openModal) {
                const closeButton = openModal.querySelector('[data-close-modal]');
                if (closeButton) {
                    closeButton.click();
                    return true;
                }
            }
            
            // Close dropdowns
            const openDropdown = document.querySelector('[data-dropdown][aria-expanded="true"]');
            if (openDropdown) {
                openDropdown.click();
                return true;
            }
        });

        this.registerShortcut('?', () => {
            // Show keyboard shortcuts help
            const helpModal = document.querySelector('[data-shortcuts-modal]');
            if (helpModal) {
                helpModal.click();
                return true;
            }
        });

        // Listen for keyboard events
        document.addEventListener('keydown', (e) => {
            // Check if user is typing in an input
            if (e.target.matches('input, textarea, select')) {
                return;
            }

            const key = e.key;
            if (this.shortcuts[key]) {
                const shouldPreventDefault = this.shortcuts[key](e);
                if (shouldPreventDefault) {
                    e.preventDefault();
                }
            }
        });
    }

    /**
     * Register a keyboard shortcut
     */
    registerShortcut(key, callback) {
        this.shortcuts[key] = callback;
    }

    /**
     * Setup focus management for modals and dialogs
     */
    setupFocusManagement() {
        // Trap focus within modals
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            const modal = document.querySelector('[role="dialog"][aria-hidden="false"]');
            if (!modal) return;

            const focusableContent = modal.querySelectorAll(this.focusableElements);
            const firstFocusable = focusableContent[0];
            const lastFocusable = focusableContent[focusableContent.length - 1];

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstFocusable) {
                    lastFocusable.focus();
                    e.preventDefault();
                }
            } else {
                // Tab
                if (document.activeElement === lastFocusable) {
                    firstFocusable.focus();
                    e.preventDefault();
                }
            }
        });

        // Focus first element when modal opens
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'aria-hidden') {
                    const modal = mutation.target;
                    if (modal.getAttribute('aria-hidden') === 'false') {
                        const firstFocusable = modal.querySelector(this.focusableElements);
                        if (firstFocusable) {
                            setTimeout(() => firstFocusable.focus(), 100);
                        }
                    }
                }
            });
        });

        document.querySelectorAll('[role="dialog"]').forEach((modal) => {
            observer.observe(modal, { attributes: true });
        });
    }

    /**
     * Setup skip links functionality
     */
    setupSkipLinks() {
        document.querySelectorAll('.skip-link').forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const target = document.getElementById(targetId);
                
                if (target) {
                    // Set tabindex to make it focusable
                    if (!target.hasAttribute('tabindex')) {
                        target.setAttribute('tabindex', '-1');
                    }
                    
                    // Focus the target
                    target.focus();
                    
                    // Scroll to target
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    /**
     * Setup ARIA live regions for dynamic content
     */
    setupAriaLiveRegions() {
        // Create a global announcement region if it doesn't exist
        if (!document.getElementById('aria-live-announcer')) {
            const announcer = document.createElement('div');
            announcer.id = 'aria-live-announcer';
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            document.body.appendChild(announcer);
        }
    }

    /**
     * Announce a message to screen readers
     */
    announce(message, priority = 'polite') {
        const announcer = document.getElementById('aria-live-announcer');
        if (announcer) {
            announcer.setAttribute('aria-live', priority);
            announcer.textContent = message;
            
            // Clear after announcement
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    }

    /**
     * Setup reduced motion preferences
     */
    setupReducedMotion() {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        
        const handleReducedMotion = (e) => {
            if (e.matches) {
                document.documentElement.classList.add('reduce-motion');
            } else {
                document.documentElement.classList.remove('reduce-motion');
            }
        };
        
        handleReducedMotion(prefersReducedMotion);
        prefersReducedMotion.addEventListener('change', handleReducedMotion);
    }

    /**
     * Get all focusable elements within a container
     */
    getFocusableElements(container) {
        return Array.from(container.querySelectorAll(this.focusableElements))
            .filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);
    }

    /**
     * Set focus to the first focusable element in a container
     */
    focusFirstElement(container) {
        const focusable = this.getFocusableElements(container);
        if (focusable.length > 0) {
            focusable[0].focus();
        }
    }

    /**
     * Manage focus for dropdown menus
     */
    setupDropdownNavigation(dropdown) {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');
        
        if (!trigger || !menu) return;

        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                trigger.click();
                setTimeout(() => this.focusFirstElement(menu), 100);
            }
        });

        menu.addEventListener('keydown', (e) => {
            const items = this.getFocusableElements(menu);
            const currentIndex = items.indexOf(document.activeElement);

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % items.length;
                    items[nextIndex].focus();
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                    items[prevIndex].focus();
                    break;
                    
                case 'Home':
                    e.preventDefault();
                    items[0].focus();
                    break;
                    
                case 'End':
                    e.preventDefault();
                    items[items.length - 1].focus();
                    break;
            }
        });
    }

    /**
     * Setup roving tabindex for component navigation
     */
    setupRovingTabindex(container, itemSelector) {
        const items = container.querySelectorAll(itemSelector);
        let currentIndex = 0;

        // Set initial tabindex
        items.forEach((item, index) => {
            item.setAttribute('tabindex', index === 0 ? '0' : '-1');
        });

        container.addEventListener('keydown', (e) => {
            if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(e.key)) {
                return;
            }

            e.preventDefault();

            switch (e.key) {
                case 'ArrowRight':
                case 'ArrowDown':
                    currentIndex = (currentIndex + 1) % items.length;
                    break;
                    
                case 'ArrowLeft':
                case 'ArrowUp':
                    currentIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
                    break;
                    
                case 'Home':
                    currentIndex = 0;
                    break;
                    
                case 'End':
                    currentIndex = items.length - 1;
                    break;
            }

            // Update tabindex and focus
            items.forEach((item, index) => {
                item.setAttribute('tabindex', index === currentIndex ? '0' : '-1');
            });
            items[currentIndex].focus();
        });
    }
}

// Initialize accessibility manager
const accessibilityManager = new AccessibilityManager();

// Export for use in other modules
window.accessibilityManager = accessibilityManager;

// Announce page changes for SPAs
if (window.history && window.history.pushState) {
    const originalPushState = window.history.pushState;
    window.history.pushState = function(...args) {
        originalPushState.apply(this, args);
        setTimeout(() => {
            const pageTitle = document.title;
            accessibilityManager.announce(`Navigated to ${pageTitle}`);
        }, 100);
    };
}

export default accessibilityManager;
