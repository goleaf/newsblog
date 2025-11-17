/**
 * Mobile Navigation Module
 * Handles mobile menu interactions, swipe gestures, and touch-friendly navigation
 * Requirements: 17.1, 17.5
 */

export class MobileNavigation {
    constructor() {
        this.menuOpen = false;
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        this.swipeThreshold = 50; // Minimum distance for swipe
        this.velocityThreshold = 0.3; // Minimum velocity for swipe
        this.menuElement = null;
        this.overlayElement = null;
        
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }
    
    setupEventListeners() {
        // Get menu elements
        this.menuElement = document.querySelector('[data-mobile-menu]');
        this.overlayElement = document.querySelector('[data-mobile-overlay]');
        
        if (!this.menuElement) return;
        
        // Touch events for swipe gestures
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        
        // Keyboard navigation
        document.addEventListener('keydown', this.handleKeyDown.bind(this));
        
        // Handle menu toggle buttons
        const toggleButtons = document.querySelectorAll('[data-mobile-menu-toggle]');
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => this.toggleMenu());
        });
        
        // Handle overlay clicks
        if (this.overlayElement) {
            this.overlayElement.addEventListener('click', () => this.closeMenu());
        }
        
        // Prevent body scroll when menu is open
        this.preventBodyScroll();
    }
    
    handleTouchStart(e) {
        this.touchStartX = e.changedTouches[0].screenX;
        this.touchStartY = e.changedTouches[0].screenY;
        this.touchStartTime = Date.now();
    }
    
    handleTouchMove(e) {
        // Prevent default only if swiping horizontally
        const touchX = e.changedTouches[0].screenX;
        const touchY = e.changedTouches[0].screenY;
        const deltaX = Math.abs(touchX - this.touchStartX);
        const deltaY = Math.abs(touchY - this.touchStartY);
        
        // If horizontal swipe is more significant than vertical
        if (deltaX > deltaY && deltaX > 10) {
            // Only prevent if menu is open or swipe starts from edge
            if (this.menuOpen || this.touchStartX < 20) {
                e.preventDefault();
            }
        }
    }
    
    handleTouchEnd(e) {
        this.touchEndX = e.changedTouches[0].screenX;
        this.touchEndY = e.changedTouches[0].screenY;
        this.touchEndTime = Date.now();
        
        this.handleSwipeGesture();
    }
    
    handleSwipeGesture() {
        const deltaX = this.touchEndX - this.touchStartX;
        const deltaY = Math.abs(this.touchEndY - this.touchStartY);
        const deltaTime = this.touchEndTime - this.touchStartTime;
        const velocity = Math.abs(deltaX) / deltaTime;
        
        // Check if it's a horizontal swipe (not vertical scroll)
        if (deltaY > Math.abs(deltaX)) return;
        
        // Swipe right to open (from left edge)
        if (deltaX > this.swipeThreshold && 
            this.touchStartX < 20 && 
            !this.menuOpen &&
            velocity > this.velocityThreshold) {
            this.openMenu();
        }
        
        // Swipe left to close (when menu is open)
        if (deltaX < -this.swipeThreshold && 
            this.menuOpen &&
            velocity > this.velocityThreshold) {
            this.closeMenu();
        }
    }
    
    handleKeyDown(e) {
        // Close menu on Escape key
        if (e.key === 'Escape' && this.menuOpen) {
            this.closeMenu();
        }
    }
    
    toggleMenu() {
        if (this.menuOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }
    
    openMenu() {
        this.menuOpen = true;
        
        // Dispatch custom event for Alpine.js or other listeners
        window.dispatchEvent(new CustomEvent('mobile-menu-open'));
        
        // Set Alpine.js data if available
        if (window.Alpine) {
            const headerComponent = document.querySelector('[x-data*="headerState"]');
            if (headerComponent && headerComponent.__x) {
                headerComponent.__x.$data.mobileMenuOpen = true;
            }
        }
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Focus first focusable element in menu
        this.focusFirstElement();
        
        // Announce to screen readers
        this.announceToScreenReader('Navigation menu opened');
    }
    
    closeMenu() {
        this.menuOpen = false;
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('mobile-menu-close'));
        
        // Set Alpine.js data if available
        if (window.Alpine) {
            const headerComponent = document.querySelector('[x-data*="headerState"]');
            if (headerComponent && headerComponent.__x) {
                headerComponent.__x.$data.mobileMenuOpen = false;
            }
        }
        
        // Restore body scroll
        document.body.style.overflow = '';
        
        // Return focus to toggle button
        const toggleButton = document.querySelector('[data-mobile-menu-toggle]');
        if (toggleButton) {
            toggleButton.focus();
        }
        
        // Announce to screen readers
        this.announceToScreenReader('Navigation menu closed');
    }
    
    focusFirstElement() {
        if (!this.menuElement) return;
        
        const focusableElements = this.menuElement.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }
    
    preventBodyScroll() {
        // Listen for menu state changes
        window.addEventListener('mobile-menu-open', () => {
            document.body.style.overflow = 'hidden';
        });
        
        window.addEventListener('mobile-menu-close', () => {
            document.body.style.overflow = '';
        });
    }
    
    announceToScreenReader(message) {
        // Create or get announcement element
        let announcer = document.getElementById('mobile-nav-announcer');
        
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'mobile-nav-announcer';
            announcer.setAttribute('role', 'status');
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.className = 'sr-only';
            document.body.appendChild(announcer);
        }
        
        // Clear and set new message
        announcer.textContent = '';
        setTimeout(() => {
            announcer.textContent = message;
        }, 100);
    }
}

// Initialize mobile navigation
if (typeof window !== 'undefined') {
    window.mobileNavigation = new MobileNavigation();
}

export default MobileNavigation;
