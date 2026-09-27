
// Ensure favorites are synchronized if navigating back via browser back button (BFCache)
window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});
