export function navigateToEvents(query = '') {
    const baseUrl = 'EventManager/view/events';
    if (query && query.trim()) {
        window.location.href = `${baseUrl}?search=${encodeURIComponent(query.trim())}`;
    } else {
        window.location.href = baseUrl;
    }
}

export function getSearchFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('search') || '';
}