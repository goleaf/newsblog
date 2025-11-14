/**
 * Notifications Store
 * Manages toast notifications
 */
export default () => ({
    notifications: [],
    nextId: 1,
    
    show(message, type = 'info', duration = 5000) {
        const id = this.nextId++;
        const notification = {
            id,
            message,
            type, // 'success', 'error', 'warning', 'info'
            duration,
            visible: true
        };
        
        this.notifications.push(notification);
        
        // Auto-dismiss after duration
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(id);
            }, duration);
        }
        
        return id;
    },
    
    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    },
    
    error(message, duration = 7000) {
        return this.show(message, 'error', duration);
    },
    
    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    },
    
    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    },
    
    dismiss(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
            notification.visible = false;
            // Remove from array after animation completes
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 300);
        }
    },
    
    dismissAll() {
        this.notifications.forEach(n => {
            n.visible = false;
        });
        setTimeout(() => {
            this.notifications = [];
        }, 300);
    }
});
