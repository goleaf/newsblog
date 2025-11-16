export function csrfToken() {
	const token = document
		.querySelector('meta[name=\"csrf-token\"]')
		?.getAttribute('content');
	if (!token) {
		console.warn('CSRF token not found in meta tags.');
	}
	return token || '';
}



