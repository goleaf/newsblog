export default {
    init() {
        // Register global function for Alpine x-data usage: x-data="contentCalendar()"
        window.contentCalendar = function () {
            return {
                draggedPostId: null,

                handleDragStart(event, postId) {
                    this.draggedPostId = postId;
                    event.dataTransfer.effectAllowed = 'move';
                },

                async handleDrop(event, date) {
                    if (!this.draggedPostId) return;

                    // Optional confirmation
                    if (!confirm(window?.i18n?.calendar_confirm_reschedule || 'Move post to this date?')) {
                        this.draggedPostId = null;
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/calendar/posts/${this.draggedPostId}/update-date`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ date }),
                        });

                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert(window?.i18n?.calendar_update_failed || 'Failed to update post date');
                        }
                    } catch (error) {
                        console.error('Error updating post date:', error);
                        alert(window?.i18n?.calendar_update_error || 'An error occurred while updating the post date');
                    }

                    this.draggedPostId = null;
                },

                async showPostsForDate(date) {
                    try {
                        const response = await fetch(`/admin/calendar/posts?date=${date}`);
                        const posts = await response.json();

                        window.dispatchEvent(new CustomEvent('show-posts', {
                            detail: { posts, date }
                        }));
                    } catch (error) {
                        console.error('Error fetching posts:', error);
                    }
                }
            };
        };
    }
};


