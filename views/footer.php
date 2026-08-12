<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?= BASE_URL ?>" class="brand" style="font-size:1.3rem;display:inline-flex;gap:2px;margin-bottom:14px">
                    <span class="brand-jp">GRUPO</span><span class="brand-market">PLATA</span>
                </a>
                <p class="footer-desc">
                    Activos y Pasivos de Santa Fe.<br>
                    <em><?= SITE_SLOGAN ?></em>
                </p>
                <a href="https://www.instagram.com/jpart.ar" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:8px;margin-top:14px;color:var(--text-muted);font-size:.85rem;text-decoration:none;transition:color .2s"
                   onmouseover="this.style.color='#e1306c'" onmouseout="this.style.color=''">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    @jpart.ar
                </a>
            </div>
            <div class="footer-col">
                <h4>Navegación</h4>
                <ul>
                    <li><a href="#inicio">Inicio</a></li>
                    <li><a href="#nosotros">Nosotros</a></li>
                    <li><a href="#servicios">Servicios</a></li>
                    <li><a href="#portfolio">Portfolio</a></li>
                    <li><a href="#testimonios">Testimonios</a></li>
                    <li><a href="<?= BASE_URL ?>/blog">Blog de Marketing</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Servicios</h4>
                <ul>
                    <li><a href="#servicios">Consultoría</a></li>
                    <li><a href="#servicios">Gestión de clientes</a></li>
                    <li><a href="#servicios">Análisis de mercado</a></li>
                    <li><a href="#servicios">Marketing digital</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contacto</h4>
                <ul>
                    <li><a href="mailto:info@grupoplata.com">info@grupoplata.com</a></li>
                    <li><a href="#contacto">Formulario de contacto</a></li>
                    <li><a href="#">Buenos Aires, Argentina</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> GRUPO PLATA. Todos los derechos reservados.</p>
            <div style="display:flex;align-items:center;gap:20px">
                <span class="footer-slogan">Empower your mind</span>
                <a href="<?= ADMIN_URL ?>/login.php" style="font-size:.75rem;color:var(--text-muted);opacity:.5;transition:opacity .2s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.5">&#128274; Admin</a>
            </div>
        </div>
    </div>
</footer>

<!-- WhatsApp flotante -->
<a class="fab-whatsapp" href="https://wa.me/51900066559?text=Hola%2C%20vengo%20del%20sitio%20Web%2C%20me%20interesa%20asesor%C3%ADa%20sobre%20sus%20servicios%20de%20marketing." target="_blank" rel="noopener" title="Escribinos por WhatsApp">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
    </svg>
</a>

<!-- Scroll to top -->
<button class="fab-top" id="fabTop" title="Volver arriba" onclick="window.scrollTo({top:0,behavior:'smooth'})">&#8679;</button>

<script>
const fabTop = document.getElementById('fabTop');
window.addEventListener('scroll', () => {
    fabTop.classList.toggle('visible', window.scrollY > 400);
});
</script>

<script src="<?= BASE_URL ?>/public/assets/js/main.js"></script>
</body>
</html>
