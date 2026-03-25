// ============================================================
// LUXEBYLUCIA — Main JavaScript
// ============================================================

const SITE_URL = document.currentScript?.getAttribute('data-site') || '';

// ── Page Loader ──────────────────────────────────────────────
window.addEventListener('load', () => {
    setTimeout(() => {
        document.getElementById('page-loader')?.classList.add('hidden');
    }, 1400);
});

// ── DOM Ready ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initSearch();
    initFlashToast();
    initAnimations();
    initParticles();
    initNewsletterForm();
});

// ── Navbar ───────────────────────────────────────────────────
function initNavbar() {
    const navbar    = document.getElementById('navbar');
    const navToggle = document.getElementById('navToggle');
    const navMenu   = document.getElementById('navMenu');

    // Scroll effect
    const onScroll = () => {
        navbar?.classList.toggle('scrolled', window.scrollY > 60);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Mobile toggle
    navToggle?.addEventListener('click', () => {
        navToggle.classList.toggle('active');
        navMenu?.classList.toggle('open');
        document.body.classList.toggle('no-scroll');
    });

    // Close menu on outside click
    document.addEventListener('click', (e) => {
        if (!navbar?.contains(e.target)) {
            navToggle?.classList.remove('active');
            navMenu?.classList.remove('open');
            document.body.classList.remove('no-scroll');
        }
    });
}

// ── AJAX Search ──────────────────────────────────────────────
function initSearch() {
    const searchToggle  = document.getElementById('searchToggle');
    const closeSearch   = document.getElementById('closeSearch');
    const searchBar     = document.getElementById('searchBar');
    const searchInput   = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    let   debounceTimer;

    searchToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        searchBar?.classList.add('open');
        searchInput?.focus();
    });

    closeSearch?.addEventListener('click', () => {
        searchBar?.classList.remove('open');
        if (searchInput) searchInput.value = '';
        if (searchResults) searchResults.innerHTML = '';
    });

    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) { searchResults.innerHTML = ''; return; }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`/luxebylucia/includes/ajax.php?action=search&q=${encodeURIComponent(q)}`);
                const data = await res.json();
                renderSearchResults(data, q);
            } catch (err) {
                console.error('Search error:', err);
            }
        }, 300);
    });

    function renderSearchResults(products, q) {
        if (!searchResults) return;
        if (!products.length) {
            searchResults.innerHTML = `<p style="color:var(--white-muted);font-size:13px;padding:12px 0">No results for "${q}"</p>`;
            return;
        }
        searchResults.innerHTML = products.map(p => `
            <a href="/luxebylucia/product.php?slug=${p.slug}" class="search-result-item">
                <img src="${p.thumb || '/luxebylucia/assets/images/placeholder.jpg'}" alt="${p.name}">
                <div class="info">
                    <div class="name">${p.name}</div>
                    <div class="price">${p.price_formatted}</div>
                </div>
            </a>
        `).join('');
    }
}

// ── Flash Toast Auto-dismiss ─────────────────────────────────
function initFlashToast() {
    const toast = document.getElementById('flash-toast');
    if (toast) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }
}

// ── Show Toast programmatically ──────────────────────────────
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fa ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
    `;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

// ── Scroll Animations ────────────────────────────────────────
function initAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
}

// ── Particle Effect (Hero) ───────────────────────────────────
function initParticles() {
    const container = document.querySelector('.hero-particles');
    if (!container) return;

    for (let i = 0; i < 30; i++) {
        const dot = document.createElement('div');
        dot.style.cssText = `
            position:absolute;
            width:${Math.random() * 3 + 1}px;
            height:${Math.random() * 3 + 1}px;
            border-radius:50%;
            background:rgba(201,168,76,${Math.random() * 0.4 + 0.1});
            left:${Math.random() * 100}%;
            top:${Math.random() * 100}%;
            animation: floatParticle ${Math.random() * 8 + 6}s ease-in-out infinite;
            animation-delay:${Math.random() * 4}s;
        `;
        container.appendChild(dot);
    }

    // Inject keyframes if not present
    if (!document.getElementById('particle-style')) {
        const style = document.createElement('style');
        style.id = 'particle-style';
        style.textContent = `
            @keyframes floatParticle {
                0%,100%{transform:translate(0,0) scale(1);opacity:0.3}
                33%{transform:translate(${Math.random()*40-20}px,${Math.random()*-60-20}px) scale(1.2);opacity:0.8}
                66%{transform:translate(${Math.random()*40-20}px,${Math.random()*-40-10}px) scale(0.9);opacity:0.5}
            }
        `;
        document.head.appendChild(style);
    }
}

// ── Cart Functions ────────────────────────────────────────────
async function addToCart(productId, quantity = 1) {
    try {
        const res = await fetch('/luxebylucia/includes/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_to_cart&product_id=${productId}&quantity=${quantity}`
        });
        const data = await res.json();

        if (data.success) {
            showToast(data.message || 'Added to cart!', 'success');
            updateCartBadge(data.cart_count);
        } else {
            showToast(data.message || 'Could not add to cart', 'error');
            if (data.redirect) window.location.href = data.redirect;
        }
    } catch (err) {
        showToast('Something went wrong', 'error');
    }
}

function updateCartBadge(count) {
    document.querySelectorAll('.cart-badge').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}

// ── Wishlist Functions ────────────────────────────────────────
async function toggleWishlist(productId, btn) {
    try {
        const res = await fetch('/luxebylucia/includes/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_wishlist&product_id=${productId}`
        });
        const data = await res.json();

        if (data.success) {
            btn?.classList.toggle('wishlisted', data.wishlisted);
            showToast(data.message, 'success');
        } else {
            showToast(data.message, 'error');
            if (data.redirect) window.location.href = data.redirect;
        }
    } catch (err) {
        showToast('Something went wrong', 'error');
    }
}

// ── Cart Page: Update Quantity ────────────────────────────────
async function updateCartQty(productId, quantity) {
    const res = await fetch('/luxebylucia/includes/ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_cart&product_id=${productId}&quantity=${quantity}`
    });
    const data = await res.json();
    if (data.success) location.reload();
    else showToast(data.message, 'error');
}

async function removeFromCart(productId) {
    const res = await fetch('/luxebylucia/includes/ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove_from_cart&product_id=${productId}`
    });
    const data = await res.json();
    if (data.success) location.reload();
}

// ── Gallery (Product Page) ────────────────────────────────────
function initGallery() {
    const mainImg  = document.getElementById('galleryMain');
    const thumbs   = document.querySelectorAll('.gallery-thumb');

    thumbs.forEach(thumb => {
        thumb.addEventListener('click', () => {
            thumbs.forEach(t => t.classList.remove('active'));
            thumb.classList.add('active');
            if (mainImg) {
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.src = thumb.dataset.src;
                    mainImg.style.opacity = '1';
                }, 200);
            }
        });
    });
}

// ── Star Rating Picker ────────────────────────────────────────
function initStarPicker() {
    const stars  = document.querySelectorAll('.star-pick');
    const hidden = document.getElementById('ratingInput');

    stars.forEach(star => {
        star.addEventListener('click', () => {
            const val = parseInt(star.dataset.val);
            if (hidden) hidden.value = val;
            stars.forEach((s, i) => s.classList.toggle('active', i < val));
        });

        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.dataset.val);
            stars.forEach((s, i) => s.classList.toggle('active', i < val));
        });
    });

    document.querySelector('.star-picker')?.addEventListener('mouseleave', () => {
        const current = parseInt(hidden?.value || 0);
        stars.forEach((s, i) => s.classList.toggle('active', i < current));
    });
}

// ── Newsletter ────────────────────────────────────────────────
function initNewsletterForm() {
    const form = document.getElementById('newsletterForm');
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = form.querySelector('input[type="email"]').value;
        showToast(`Thank you! You've joined our exclusive list.`, 'success');
        form.reset();
    });
}

// ── Paystack Checkout ─────────────────────────────────────────
function initPaystack(config) {
    const handler = PaystackPop.setup({
        key:       config.publicKey,
        email:     config.email,
        amount:    config.amount * 100,  // kobo
        currency:  'NGN',
        ref:       config.ref,
        metadata:  { order_id: config.orderId, custom_fields: [] },
        callback:  function(response) {
            window.location.href = `/luxebylucia/checkout.php?verify=${response.reference}&order_id=${config.orderId}`;
        },
        onClose: function() {
            showToast('Payment cancelled. Your order is saved.', 'error');
        }
    });
    handler.openIframe();
}

// ── Admin: Image Preview ──────────────────────────────────────
function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    if (!preview) return;
    preview.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:80px;height:100px;object-fit:cover;border-radius:4px;border:1px solid var(--border)';
            preview.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

// ── Qty Buttons ───────────────────────────────────────────────
document.addEventListener('click', (e) => {
    if (e.target.closest('.qty-btn')) {
        const btn   = e.target.closest('.qty-btn');
        const input = btn.closest('.qty-selector')?.querySelector('.qty-input');
        if (!input) return;
        let val = parseInt(input.value) || 1;
        const max = parseInt(input.max) || 999;
        if (btn.dataset.action === 'plus')  val = Math.min(val + 1, max);
        if (btn.dataset.action === 'minus') val = Math.max(val - 1, 1);
        input.value = val;

        // If cart page update
        const productId = input.dataset.product;
        if (productId) updateCartQty(productId, val);
    }
});

// Run page-specific inits
if (document.querySelector('.product-gallery')) initGallery();
if (document.querySelector('.star-picker')) initStarPicker();
