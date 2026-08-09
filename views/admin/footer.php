    </div><!-- /page-content -->
</div><!-- /main -->

<script>
function openAreaDev() {
    document.getElementById('areaDevModal').style.display = 'flex';
    document.getElementById('areaDevPassword').focus();
}

function closeAreaDev() {
    document.getElementById('areaDevModal').style.display = 'none';
    document.getElementById('areaDevPassword').value = '';
    document.getElementById('areaDevError').style.display = 'none';
}

function validateAreaDev() {
    const pwd = document.getElementById('areaDevPassword').value.trim();
    if (pwd === '1396') {
        window.location.href = '<?= ADMIN_URL ?>/pages/area-dev.php';
    } else {
        document.getElementById('areaDevError').textContent = '❌ Contraseña incorrecta';
        document.getElementById('areaDevError').style.display = 'block';
    }
}

document.addEventListener('keypress', function(e) {
    const modal = document.getElementById('areaDevModal');
    if (modal && modal.style.display === 'flex' && e.key === 'Enter') {
        validateAreaDev();
    }
});

// Cerrar modal al hacer click fuera
document.getElementById('areaDevModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAreaDev();
});
</script>

</body>
</html>
