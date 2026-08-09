    </div><!-- /page-content -->
</div><!-- /main -->

<script>
function openAreaDev() {
    document.getElementById('areaDev Modal').style.display = 'flex';
    document.getElementById('areaDev Password').focus();
}

function closeAreaDev() {
    document.getElementById('areaDev Modal').style.display = 'none';
    document.getElementById('areaDev Password').value = '';
    document.getElementById('areaDev Error').style.display = 'none';
}

function validateAreaDev() {
    const pwd = document.getElementById('areaDev Password').value.trim();
    if (pwd === '1396') {
        window.location.href = '<?= ADMIN_URL ?>/pages/area-dev.php';
    } else {
        document.getElementById('areaDev Error').textContent = '❌ Contraseña incorrecta';
        document.getElementById('areaDev Error').style.display = 'block';
    }
}

document.addEventListener('keypress', function(e) {
    const modal = document.getElementById('areaDev Modal');
    if (modal && modal.style.display === 'flex' && e.key === 'Enter') {
        validateAreaDev();
    }
});

// Cerrar modal al hacer click fuera
document.getElementById('areaDev Modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAreaDev();
});
</script>

</body>
</html>
