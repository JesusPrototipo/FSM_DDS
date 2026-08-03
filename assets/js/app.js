// WEBDDS — app.js
// Espacio para JS global del proyecto (confirmaciones, etc.)

// Confirmación antes de eliminar
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm || '¿Estás seguro?')) {
            e.preventDefault();
        }
    });
});
