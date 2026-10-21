(function () {
    var dismiss = document.getElementById('settings-updated-success-dismiss');
    // The saved notice is only rendered right after a save.
    if (!dismiss) {
        return;
    }
    dismiss.addEventListener('click', function () {
        document.getElementById('settings-updated-success').classList.add('hidden');
    });
})();
