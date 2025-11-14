/**
 * Modal Store
 * Manages modal state and focus trapping
 */
export default () => ({
    modals: {},
    activeModal: null,
    previousFocus: null,
    
    register(id) {
        if (!this.modals[id]) {
            this.modals[id] = {
                id,
                open: false,
                data: null
            };
        }
    },
    
    open(id, data = null) {
        // Store current focus to restore later
        this.previousFocus = document.activeElement;
        
        // Close any open modal first
        if (this.activeModal && this.activeModal !== id) {
            this.close(this.activeModal);
        }
        
        if (this.modals[id]) {
            this.modals[id].open = true;
            this.modals[id].data = data;
            this.activeModal = id;
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            // Focus first focusable element in modal
            this.$nextTick(() => {
                const modal = document.querySelector(`[data-modal-id="${id}"]`);
                if (modal) {
                    const focusable = modal.querySelector(
                        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                    );
                    if (focusable) {
                        focusable.focus();
                    }
                }
            });
        }
    },
    
    close(id) {
        if (this.modals[id]) {
            this.modals[id].open = false;
            this.modals[id].data = null;
            
            if (this.activeModal === id) {
                this.activeModal = null;
            }
            
            // Restore body scroll
            document.body.style.overflow = '';
            
            // Restore previous focus
            if (this.previousFocus) {
                this.previousFocus.focus();
                this.previousFocus = null;
            }
        }
    },
    
    closeAll() {
        Object.keys(this.modals).forEach(id => {
            this.close(id);
        });
    },
    
    isOpen(id) {
        return this.modals[id]?.open || false;
    },
    
    getData(id) {
        return this.modals[id]?.data || null;
    },
    
    handleEscape(event) {
        if (event.key === 'Escape' && this.activeModal) {
            this.close(this.activeModal);
        }
    }
});
