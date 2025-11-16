import { csrfToken } from './csrf';

function updateIconState(button, bookmarked) {
	const outline = button.querySelector('[data-bookmark-outline]');
	const filled = button.querySelector('[data-bookmark-filled]');
	if (!outline || !filled) {
		return;
	}
	if (bookmarked) {
		outline.classList.add('hidden');
		outline.classList.remove('opacity-100');
		filled.classList.remove('hidden');
	} else {
		filled.classList.add('hidden');
		outline.classList.remove('hidden');
		outline.classList.add('opacity-100');
	}
}

export function initBookmarkToggles() {
	document.querySelectorAll('[data-bookmark-toggle]').forEach((button) => {
		if (button.dataset.bound === '1') {
			return;
		}
		button.dataset.bound = '1';
		button.addEventListener('click', async () => {
			const postId = button.getAttribute('data-post-id');
			if (!postId) return;
			button.disabled = true;
			button.classList.add('opacity-60');
			try {
				const res = await fetch('/bookmarks/toggle', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': csrfToken(),
						'Accept': 'application/json',
					},
					body: JSON.stringify({ post_id: Number(postId) }),
					credentials: 'same-origin',
				});
				if (!res.ok) throw new Error('Failed to toggle bookmark');
				const data = await res.json();
				updateIconState(button, Boolean(data.bookmarked));
			} catch (e) {
				// no-op; could add toast
				console.error(e);
			} finally {
				button.disabled = false;
				button.classList.remove('opacity-60');
			}
		});
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initBookmarkToggles();
});



