<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/functions.php';
$pageTitle = 'Inicio';
?>
<?php include __DIR__ . '/../views/header.php'; ?>

<!-- ====== HERO ====== -->
<section class="hero" id="inicio">
    <div class="hero-bg" aria-hidden="true">
        <div class="hbg-orb hbg-orb--1"></div>
        <div class="hbg-orb hbg-orb--2"></div>
        <div class="hbg-orb hbg-orb--3"></div>
    </div>
    <div class="hero-inner">
        <div class="hero-badge"><?= SITE_NAME ?> — <?= SITE_SLOGAN ?></div>

        <h1><span class="jp">GRUPO</span>PLATA</h1>
        <p class="hero-slogan"><?= SITE_SLOGAN ?></p>

        <p class="hero-desc">
            Créditos seguros y accesibles para activos, pasivos, provinciales y jubilados de Santa Fe.
            Financiamiento pensado para vos.
        </p>

        <div class="hero-actions">
            <a href="#servicios" class="btn-primary">Nuestros servicios &#8594;</a>
            <a href="#contacto"  class="btn-ghost">Hablemos</a>
        </div>

        <div class="hero-stats">
            <div>
                <div class="hero-stat-val">100<span>+</span></div>
                <div class="hero-stat-lbl">Clientes activos</div>
            </div>
            <div>
                <div class="hero-stat-val">98<span>%</span></div>
                <div class="hero-stat-lbl">Satisfacción</div>
            </div>
            <div>
                <div class="hero-stat-val">5<span>+</span></div>
                <div class="hero-stat-lbl">Años de experiencia</div>
            </div>
            <div>
                <div class="hero-stat-val">24<span>/7</span></div>
                <div class="hero-stat-lbl">Soporte</div>
            </div>
        </div>
    </div>
</section>


<!-- ====== NOSOTROS ====== -->
<section class="section section-alt" id="nosotros">
    <div class="container">
        <div class="about-grid">
            <div>
                <span class="section-tag">Quiénes somos</span>
                <h2 class="section-title">Créditos pensados<br>para <span>vos</span></h2>
                <p class="section-lead">
                    En <?= SITE_NAME ?> creemos en el poder de los activos y pasivos. Trabajamos para conectar información financiera clara y accesible para familias de Santa Fe.
                </p>

                <div class="about-list">
                    <div class="about-item">
                        <div class="about-icon">💳</div>
                        <div>
                            <h4>Créditos accesibles</h4>
                            <p>Tasas justas y flexibles para activos, pasivos, provinciales y jubilados de Santa Fe.</p>
                        </div>
                    </div>
                    <div class="about-item">
                        <div class="about-icon">⚡</div>
                        <div>
                            <h4>Trámite rápido</h4>
                            <p>Aprobación en 24 horas. Documentación simple. Sin vueltas ni sorpresas.</p>
                        </div>
                    </div>
                    <div class="about-item">
                        <div class="about-icon">🔒</div>
                        <div>
                            <h4>100% Seguro</h4>
                            <p>Tu información está protegida. Transacciones seguras y transparentes.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-card">
                <div class="about-card-stat">
                    <div class="num">100+</div>
                    <div class="lbl">Clientes que confían en JP MARKET</div>
                </div>
                <div class="divider"></div>
                <div class="about-card-stat">
                    <div class="num">5+</div>
                    <div class="lbl">Años transformando ideas en resultados</div>
                </div>
                <div class="divider"></div>
                <div class="about-card-stat">
                    <div class="num">98%</div>
                    <div class="lbl">Índice de satisfacción de clientes</div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ====== SERVICIOS ====== -->
<section class="section" id="servicios">
    <div class="container">
        <div class="services-header">
            <div>
                <span class="section-tag">Lo que hacemos</span>
                <h2 class="section-title">Nuestros <span>servicios</span></h2>
            </div>
            <p class="section-lead" style="max-width:320px;color:var(--text);font-weight:500">
                Soluciones de crédito diseñadas para tu perfil financiero.
            </p>
        </div>

        <div class="services-grid">
            <div class="service-card">
                <div class="service-num">01</div>
                <div class="service-icon">💰</div>
                <h3>Créditos para Activos</h3>
                <p>Financiamiento flexible para personas en actividad. Tasas competitivas sin requisitos imposibles.</p>
            </div>
            <div class="service-card">
                <div class="service-num">02</div>
                <div class="service-icon">🏛️</div>
                <h3>Créditos para Pasivos & Jubilados</h3>
                <p>Créditos pensados especialmente para jubilados y pasivos de la provincia de Santa Fe.</p>
            </div>
            <div class="service-card">
                <div class="service-num">03</div>
                <div class="service-icon">📋</div>
                <h3>Créditos Provinciales</h3>
                <p>Programas especiales para empleados públicos y trabajadores provinciales con beneficios exclusivos.</p>
            </div>
            <div class="service-card">
                <div class="service-num">04</div>
                <div class="service-icon">&#128187;</div>
                <h3>Desarrollo Web</h3>
                <p>Sitios modernos, rápidos y con <strong style="color:var(--accent)">panel de administración propio</strong> — editá contenido, leads y clientes sin depender de nadie.</p>
            </div>
            <div class="service-card">
                <div class="service-num">05</div>
                <div class="service-icon">&#128640;</div>
                <h3>Growth Strategy</h3>
                <p>Diseñamos estrategias de crecimiento basadas en datos reales, no suposiciones. Escalabilidad con dirección.</p>
            </div>
            <div class="service-card">
                <div class="service-num">06</div>
                <div class="service-icon">&#128200;</div>
                <h3>Analytics & Optimización</h3>
                <p>Analizamos comportamiento y rendimiento para mejorar cada etapa del embudo y bajar el costo por conversión.</p>
            </div>
        </div>
    </div>
</section>

<!-- ====== PORTFOLIO ====== -->
<section class="section section-alt" id="portfolio">
    <div class="container">
        <div style="text-align:center;max-width:560px;margin:0 auto 48px">
            <span class="section-tag">Nuestro trabajo</span>
            <h2 class="section-title">Portfolio de <span>proyectos</span></h2>
            <p class="section-lead" style="margin:0 auto">
                Sitios web que diseñamos y desarrollamos para nuestros clientes. Rápidos, modernos y con panel propio.
            </p>
        </div>

        <!-- Filtros -->
        <div class="pf-filters">
            <button class="pf-pill pf-pill--active" data-filter="all">Todos <span>3</span></button>
            <button class="pf-pill" data-filter="ecommerce">E-commerce <span>2</span></button>
            <button class="pf-pill" data-filter="salud">Salud <span>1</span></button>
        </div>

        <div class="pf-grid" id="pfGrid">

            <!-- Tasty Pet Food -->
            <div class="pf-card pf-card--amber" data-cat="ecommerce">
                <div class="pf-glare"></div>
                <a href="https://tastypetfood.com.ar" target="_blank" rel="noopener" class="pf-visual pf-visual--screenshot">
                    <div class="pf-glow"></div>
                    <div class="pf-browser-frame">
                        <div class="pf-browser-bar">
                            <span class="pf-bdot pf-bdot--r"></span>
                            <span class="pf-bdot pf-bdot--y"></span>
                            <span class="pf-bdot pf-bdot--g"></span>
                            <span class="pf-browser-url">tastypetfood.com.ar</span>
                        </div>
                        <div class="pf-browser-screen">
                            <img src="<?= BASE_URL ?>/public/assets/img/portfolio/tastypetfood.png" alt="Diseño web e-commerce salsas para mascotas Tasty Pet Food Argentina" loading="lazy">
                        </div>
                    </div>
                </a>
                <div class="pf-body">
                    <div class="pf-cat">SALSAS &amp; CONDIMENTOS · MASCOTAS</div>
                    <h3 class="pf-name">Tasty Pet Food</h3>
                    <div class="pf-loc">&#128205; Argentina</div>
                    <div class="pf-tags">
                        <span>PHP</span><span>MySQL</span><span>JavaScript</span><span>E-commerce</span><span>Responsive</span>
                    </div>
                    <a href="https://tastypetfood.com.ar" target="_blank" rel="noopener" class="pf-cta">
                        Ver sitio en vivo <span>&#8594;</span>
                    </a>
                </div>
            </div>

            <!-- C.E.R.Y.D.E -->
            <div class="pf-card pf-card--blue" data-cat="salud">
                <div class="pf-glare"></div>
                <a href="https://ceryde.com.ar" target="_blank" rel="noopener" class="pf-visual pf-visual--screenshot">
                    <div class="pf-glow"></div>
                    <div class="pf-browser-frame">
                        <div class="pf-browser-bar">
                            <span class="pf-bdot pf-bdot--r"></span>
                            <span class="pf-bdot pf-bdot--y"></span>
                            <span class="pf-bdot pf-bdot--g"></span>
                            <span class="pf-browser-url">ceryde.com.ar</span>
                        </div>
                        <div class="pf-browser-screen">
                            <img src="<?= BASE_URL ?>/public/assets/img/portfolio/ceryde.png" alt="Diseño web centro kinesiología CERYDE Avellaneda Buenos Aires" loading="lazy">
                        </div>
                    </div>
                </a>
                <div class="pf-body">
                    <div class="pf-cat">CENTRO DE KINESIOLOGÍA · SALUD</div>
                    <h3 class="pf-name">C.E.R.Y.D.E</h3>
                    <div class="pf-loc">&#128205; Avellaneda, Argentina</div>
                    <div class="pf-tags">
                        <span>HTML5</span><span>CSS3</span><span>JavaScript</span><span>Salud</span><span>Responsive</span>
                    </div>
                    <a href="https://ceryde.com.ar" target="_blank" rel="noopener" class="pf-cta">
                        Ver sitio en vivo <span>&#8594;</span>
                    </a>
                </div>
            </div>

            <!-- Apio Sagrado -->
            <div class="pf-card pf-card--green" data-cat="ecommerce">
                <div class="pf-glare"></div>
                <a href="https://apiosagrado.com.ar" target="_blank" rel="noopener" class="pf-visual pf-visual--screenshot">
                    <div class="pf-glow"></div>
                    <div class="pf-browser-frame">
                        <div class="pf-browser-bar">
                            <span class="pf-bdot pf-bdot--r"></span>
                            <span class="pf-bdot pf-bdot--y"></span>
                            <span class="pf-bdot pf-bdot--g"></span>
                            <span class="pf-browser-url">apiosagrado.com.ar</span>
                        </div>
                        <div class="pf-browser-screen">
                            <img src="<?= BASE_URL ?>/public/assets/img/portfolio/apiosagrado.png" alt="Diseño web verdulería online B2B Apio Sagrado Argentina" loading="lazy">
                        </div>
                    </div>
                </a>
                <div class="pf-body">
                    <div class="pf-cat">VERDULERÍA ONLINE · B2B</div>
                    <h3 class="pf-name">Apio Sagrado</h3>
                    <div class="pf-loc">&#128205; Argentina</div>
                    <div class="pf-tags">
                        <span>PHP</span><span>CSS3</span><span>JavaScript</span><span>E-commerce</span><span>B2B</span>
                    </div>
                    <a href="https://apiosagrado.com.ar" target="_blank" rel="noopener" class="pf-cta">
                        Ver sitio en vivo <span>&#8594;</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
(function(){
    // ── Filtros ──────────────────────────────────────────────────────────────
    document.querySelectorAll('.pf-pill').forEach(function(pill){
        pill.addEventListener('click', function(){
            document.querySelectorAll('.pf-pill').forEach(function(p){ p.classList.remove('pf-pill--active'); });
            this.classList.add('pf-pill--active');
            var filter = this.dataset.filter;
            document.querySelectorAll('.pf-card').forEach(function(card){
                card.style.display = (filter === 'all' || card.dataset.cat === filter) ? '' : 'none';
            });
        });
    });

    // ── 3D Tilt (solo hover, no mobile) ──────────────────────────────────────
    var isMobile = window.matchMedia('(hover: none)').matches;
    if (isMobile) return;

    document.querySelectorAll('.pf-card').forEach(function(card){
        var glare = card.querySelector('.pf-glare');

        card.addEventListener('mousemove', function(e){
            var rect  = card.getBoundingClientRect();
            var x     = e.clientX - rect.left;
            var y     = e.clientY - rect.top;
            var cx    = rect.width  / 2;
            var cy    = rect.height / 2;
            var rotX  = ((y - cy) / cy) * -10;
            var rotY  = ((x - cx) / cx) *  12;
            var scale = 1.03;

            card.style.transform = 'perspective(900px) rotateX('+rotX+'deg) rotateY('+rotY+'deg) scale3d('+scale+','+scale+','+scale+')';
            card.style.transition = 'transform 0.08s ease';

            if (glare) {
                var angle  = Math.atan2(y - cy, x - cx) * (180 / Math.PI) + 90;
                var dist   = Math.sqrt(Math.pow((x-cx)/cx,2) + Math.pow((y-cy)/cy,2));
                var opac   = Math.min(dist * 0.35, 0.28);
                glare.style.background = 'linear-gradient('+angle+'deg, rgba(255,255,255,'+opac+') 0%, rgba(255,255,255,0) 60%)';
                glare.style.opacity = '1';
            }
        });

        card.addEventListener('mouseleave', function(){
            card.style.transform  = '';
            card.style.transition = 'transform 0.5s ease';
            if (glare) { glare.style.opacity = '0'; }
        });
    });
})();
</script>

<!-- ====== LEAD FORM ====== -->
<section class="section lead-section" id="empecemos">
    <div class="container">

        <div class="lead-header">
            <span class="section-tag">Trabajemos juntos</span>
            <h2 class="section-title">¿Listo para <span>escalar</span> tu negocio?</h2>
            <p class="section-lead">
                Completá el formulario y nos ponemos en contacto en menos de 24 hs con una propuesta personalizada.
            </p>
        </div>

        <div class="lead-card">
            <form id="leadForm" action="<?= BASE_URL ?>/public/send-lead.php" method="POST" novalidate>

                <!-- Fila 1 -->
                <div class="lead-row">
                    <div class="lead-group">
                        <label for="l_nombre">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="l_nombre" name="nombre" required placeholder="Ej: María García">
                    </div>
                    <div class="lead-group">
                        <label for="l_email">Email <span class="req">*</span></label>
                        <input type="email" id="l_email" name="email" required placeholder="tu@empresa.com">
                    </div>
                    <div class="lead-group">
                        <label for="l_whatsapp">WhatsApp</label>
                        <input type="tel" id="l_whatsapp" name="whatsapp" placeholder="+54 11 1234-5678">
                    </div>
                </div>

                <!-- Fila 2 -->
                <div class="lead-row">
                    <div class="lead-group">
                        <label for="l_nicho">Nicho / Industria <span class="req">*</span></label>
                        <select id="l_nicho" name="nicho" required>
                            <option value="" disabled selected>Seleccioná tu industria</option>
                            <option>E-commerce / Tienda online</option>
                            <option>Salud y bienestar</option>
                            <option>Gastronomía / Restaurantes</option>
                            <option>Inmobiliaria / Real estate</option>
                            <option>Educación / Cursos</option>
                            <option>Tecnología / SaaS</option>
                            <option>Moda / Indumentaria</option>
                            <option>Servicios profesionales</option>
                            <option>Construcción / Arquitectura</option>
                            <option>Entretenimiento / Medios</option>
                            <option>Otro</option>
                        </select>
                    </div>
                    <div class="lead-group">
                        <label for="l_ciudad">Ciudad <span class="req">*</span></label>
                        <input type="text" id="l_ciudad" name="ciudad" required placeholder="Ej: Buenos Aires">
                    </div>
                    <div class="lead-group">
                        <label for="l_pais">País <span class="req">*</span></label>
                        <select id="l_pais" name="pais" required>
                            <option value="" disabled selected>Seleccioná tu país</option>
                            <option>Argentina</option>
                            <option>México</option>
                            <option>Colombia</option>
                            <option>Chile</option>
                            <option>Perú</option>
                            <option>Uruguay</option>
                            <option>Venezuela</option>
                            <option>Ecuador</option>
                            <option>Bolivia</option>
                            <option>Paraguay</option>
                            <option>España</option>
                            <option>Estados Unidos</option>
                            <option>Otro</option>
                        </select>
                    </div>
                </div>

                <!-- Presupuesto -->
                <div class="lead-group lead-group--full">
                    <label>Inversión mensual en marketing <span class="req">*</span></label>
                    <p class="lead-hint">Seleccioná el rango con el que te sentís cómodo para empezar</p>
                    <div class="budget-grid">
                        <label class="budget-card">
                            <input type="radio" name="presupuesto" value="$500 - $1,000 USD" required>
                            <div class="budget-card-inner">
                                <div class="budget-amount">$500 – $1K</div>
                                <div class="budget-label">USD / mes</div>
                                <div class="budget-desc">Ideal para comenzar y testear canales</div>
                            </div>
                        </label>
                        <label class="budget-card">
                            <input type="radio" name="presupuesto" value="$1,000 - $3,000 USD">
                            <div class="budget-card-inner">
                                <div class="budget-amount">$1K – $3K</div>
                                <div class="budget-label">USD / mes</div>
                                <div class="budget-desc">Campañas multicanal con escala real</div>
                            </div>
                        </label>
                        <label class="budget-card">
                            <input type="radio" name="presupuesto" value="$3,000 - $5,000 USD">
                            <div class="budget-card-inner">
                                <div class="budget-amount">$3K – $5K</div>
                                <div class="budget-label">USD / mes</div>
                                <div class="budget-desc">Estrategia avanzada y dominación de mercado</div>
                            </div>
                        </label>
                        <label class="budget-card budget-card--top">
                            <input type="radio" name="presupuesto" value="$5,000+ USD">
                            <div class="budget-card-inner">
                                <div class="budget-badge">TOP</div>
                                <div class="budget-amount">$5K+</div>
                                <div class="budget-label">USD / mes</div>
                                <div class="budget-desc">Gestión full-service y resultados garantizados</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Objetivo -->
                <div class="lead-group lead-group--full">
                    <label for="l_objetivo">¿Cuál es tu objetivo principal?</label>
                    <div class="goal-grid">
                        <?php foreach([
                            ['🚀','Conseguir más clientes'],
                            ['📈','Aumentar mis ventas'],
                            ['🎯','Mejorar mi marca'],
                            ['🔄','Automatizar mi marketing'],
                        ] as [$icon,$goal]): ?>
                        <label class="goal-chip">
                            <input type="radio" name="objetivo" value="<?= $goal ?>">
                            <span><?= $icon ?> <?= $goal ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="lead-submit-row">
                    <button type="submit" class="btn-lead-submit">
                        Quiero que me contacten &#8594;
                    </button>
                    <p class="lead-privacy">&#128274; Sin spam. Te contactamos en menos de 24 hs.</p>
                </div>

            </form>
        </div>

    </div>
</section>

<!-- ====== TESTIMONIOS ====== -->
<section class="section section-alt" id="testimonios">
    <div class="container">
        <div style="text-align:center; max-width:560px; margin:0 auto 20px">
            <span class="section-tag">Lo que dicen</span>
            <h2 class="section-title">Nuestros <span>clientes</span> hablan</h2>
            <p class="section-lead" style="margin:0 auto">
                Más de 100 empresas y profesionales ya confían en JP MARKET para potenciar su crecimiento.
            </p>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="testimonial-quote">
                    JP MARKET transformó completamente la forma en que gestionamos nuestros clientes.
                    Los resultados fueron visibles desde el primer mes.
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">MA</div>
                    <div>
                        <div class="author-name">Martín Alvarez</div>
                        <div class="author-role">CEO, Alvarez & Asociados</div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="testimonial-quote">
                    El equipo de JP MARKET entiende el negocio desde adentro. Su consultoría estratégica
                    nos ayudó a duplicar nuestras ventas en 6 meses.
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">LR</div>
                    <div>
                        <div class="author-name">Laura Rodríguez</div>
                        <div class="author-role">Directora Comercial, TechNova</div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                <p class="testimonial-quote">
                    Lo que más valoro es la disponibilidad del equipo y los datos precisos que nos
                    dan para tomar decisiones. Son un aliado estratégico real.
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">GP</div>
                    <div>
                        <div class="author-name">Gonzalo Pérez</div>
                        <div class="author-role">Fundador, GreenPath</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ====== CONTACTO ====== -->
<section class="section" id="contacto">
    <div class="container">
        <div style="text-align:center;max-width:560px;margin:0 auto 48px">
            <span class="section-tag">Hablemos</span>
            <h2 class="section-title">¿Listo para <span>crecer</span>?</h2>
            <p class="section-lead" style="margin:0 auto">
                Tenés una idea o un proyecto en mente? Estamos para escucharte y ayudarte a llevarlo al siguiente nivel.
            </p>
        </div>

        <div class="contact-items-row">
            <div class="contact-item">
                <div class="contact-item-icon">&#128231;</div>
                <div>
                    <h4>Email</h4>
                    <p><a href="mailto:info@jpmarket.com" style="color:var(--accent)">info@jpmarket.com</a></p>
                </div>
            </div>
            <div class="contact-item-sep"></div>
            <div class="contact-item">
                <div class="contact-item-icon">&#128205;</div>
                <div>
                    <h4>Ubicación</h4>
                    <p>Buenos Aires, Argentina</p>
                </div>
            </div>
            <div class="contact-item-sep"></div>
            <div class="contact-item">
                <div class="contact-item-icon">&#9200;</div>
                <div>
                    <h4>Horario</h4>
                    <p>Lun–Vie, 9:00 a 18:00 hs</p>
                </div>
            </div>
        </div>

        <div style="text-align:center;margin-top:40px">
            <a href="#empecemos" class="btn-primary">Completá el formulario &#8594;</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../views/footer.php'; ?>
