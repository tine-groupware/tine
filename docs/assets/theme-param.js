(function () {
    const params = new URLSearchParams(window.location.search);
    const theme = params.get("theme");
    if (!theme) return;

    const index = theme === "dark" ? 1 : theme === "light" ? 0 : null;
    if (index === null) return;

    const key = "/.__palette";
    const current = JSON.parse(localStorage.getItem(key) || "{}");

    if (current.index !== index) {
        localStorage.setItem(key, JSON.stringify({ index }));
        // URL-Parameter entfernen und neu laden
        const url = new URL(window.location.href);
        url.searchParams.delete("theme");
        window.location.replace(url.toString());
    }
})();


// open all external links in a new tab (to not confuse the user in popup manual)
document$.subscribe(() => {
    document.querySelectorAll('a[href^="http"], a[href^="//"]').forEach(link => {
        if (link.hostname !== window.location.hostname) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });
});