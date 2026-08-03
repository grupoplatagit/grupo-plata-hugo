document.addEventListener('DOMContentLoaded', () => {

    // ── Navbar scroll effect ──────────────────────────────────────
    const header = document.getElementById('siteHeader');
    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });

    // ── Mobile nav toggle ─────────────────────────────────────────
    const toggle   = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (toggle && navLinks) {
        toggle.addEventListener('click', () => navLinks.classList.toggle('open'));
        navLinks.querySelectorAll('a').forEach(a =>
            a.addEventListener('click', () => navLinks.classList.remove('open'))
        );
    }

    // ── Active nav link on scroll ─────────────────────────────────
    const sections = document.querySelectorAll('section[id]');
    const links    = document.querySelectorAll('.nav-links a[href^="#"]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                links.forEach(l => l.classList.remove('active'));
                const active = document.querySelector(`.nav-links a[href="#${entry.target.id}"]`);
                if (active) active.classList.add('active');
            }
        });
    }, { threshold: 0.4 });

    sections.forEach(s => observer.observe(s));

    // ── Service cards: entrada 3D escalonada ──────────────────────
    const cards = document.querySelectorAll('.service-card');
    if (cards.length) {
        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const card  = entry.target;
                    const index = [...cards].indexOf(card);
                    card.style.animationDelay = (index * 110) + 'ms';
                    card.classList.add('revealed');
                    cardObserver.unobserve(card);
                }
            });
        }, { threshold: 0.12 });
        cards.forEach(c => cardObserver.observe(c));

        // Tilt 3D en hover (sigue el mouse)
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect   = card.getBoundingClientRect();
                const cx     = rect.left + rect.width  / 2;
                const cy     = rect.top  + rect.height / 2;
                const dx     = (e.clientX - cx) / (rect.width  / 2);
                const dy     = (e.clientY - cy) / (rect.height / 2);
                const tiltX  = dy * -8;
                const tiltY  = dx *  8;
                card.style.transform = `perspective(800px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) scale(1.03)`;
                card.style.boxShadow = `${-tiltY * 2}px ${tiltX * 2}px 30px rgba(6,182,212,0.15)`;
                card.style.borderColor = 'rgba(6,182,212,0.4)';
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform   = 'perspective(800px) rotateX(0) rotateY(0) scale(1)';
                card.style.boxShadow   = '';
                card.style.borderColor = '';
                card.style.transition  = 'transform .4s ease, box-shadow .4s ease, border-color .3s';
            });
            card.addEventListener('mouseenter', () => {
                card.style.transition = 'none';
            });
        });
    }

    // ── Contact form (AJAX) ───────────────────────────────────────
    const form = document.getElementById('contactForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('.btn-submit');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const res  = await fetch(form.action, { method: 'POST', body: new FormData(form) });
                const json = await res.json();
                toast(json.ok ? 'success' : 'error', json.message);
                if (json.ok) form.reset();
            } catch {
                toast('error', 'Error al enviar. Intentá de nuevo.');
            } finally {
                btn.disabled = false;
                btn.textContent = original;
            }
        });
    }

    // ── Lead form (AJAX) ──────────────────────────────────────────
    const leadForm = document.getElementById('leadForm');
    if (leadForm) {
        leadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = leadForm.querySelector('.btn-lead-submit');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Enviando...';

            try {
                const res  = await fetch(leadForm.action, { method: 'POST', body: new FormData(leadForm) });
                const json = await res.json();
                toast(json.ok ? 'success' : 'error', json.msg);
                if (json.ok) {
                    leadForm.reset();
                    leadForm.querySelectorAll('.budget-card-inner, .goal-chip span').forEach(el => el.style.cssText = '');
                }
            } catch {
                toast('error', 'Error al enviar. Intentá de nuevo.');
            } finally {
                btn.disabled = false;
                btn.textContent = original;
            }
        });
    }

    function toast(type, msg) {
        const t = document.createElement('div');
        t.textContent = msg;
        Object.assign(t.style, {
            position: 'fixed', bottom: '28px', right: '28px', zIndex: '9999',
            padding: '14px 22px', borderRadius: '10px', fontWeight: '600',
            fontSize: '.9rem', maxWidth: '320px',
            background: type === 'success' ? '#16a34a' : '#dc2626',
            color: '#fff', boxShadow: '0 8px 32px rgba(0,0,0,0.4)',
            transform: 'translateY(20px)', opacity: '0',
            transition: 'all .3s ease',
        });
        document.body.appendChild(t);
        requestAnimationFrame(() => {
            t.style.transform = 'translateY(0)';
            t.style.opacity   = '1';
        });
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transform = 'translateY(20px)';
            setTimeout(() => t.remove(), 300);
        }, 4000);
    }

});
