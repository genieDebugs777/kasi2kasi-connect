/**
 * Kasi2Kasi Connect - Animations & Interactive Features
 * Adds smooth UI interactions, loading states, and visual feedback
 */

document.addEventListener("DOMContentLoaded", function() {
    
    // ============================================================
    // 1. PAGE LOADING ANIMATION
    // ============================================================
    const mainContent = document.querySelector('.container');
    if (mainContent) {
        mainContent.style.opacity = '0';
        mainContent.style.transform = 'translateY(20px)';
        mainContent.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
        }, 100);
    }

    // ============================================================
    // 2. BUTTON CLICK RIPPLE EFFECT
    // ============================================================
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${e.clientX - rect.left - size/2}px`;
            ripple.style.top = `${e.clientY - rect.top - size/2}px`;
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(255,255,255,0.4)';
            ripple.style.pointerEvents = 'none';
            ripple.style.transform = 'scale(0)';
            ripple.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.style.transform = 'scale(1)';
                ripple.style.opacity = '0';
            }, 10);
            
            setTimeout(() => ripple.remove(), 400);
        });
    });

    // ============================================================
    // 3. PRODUCT CARD HOVER EFFECT (already in CSS, but adds parallax)
    // ============================================================
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;
            
            const img = this.querySelector('.img');
            if (img) {
                img.style.transform = `scale(1.02) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                img.style.transition = 'transform 0.2s ease';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const img = this.querySelector('.img');
            if (img) {
                img.style.transform = 'scale(1) rotateX(0) rotateY(0)';
            }
        });
    });

    // ============================================================
    // 4. CART ITEM QUANTITY ANIMATION
    // ============================================================
    const quantityInputs = document.querySelectorAll('.cart-row input[type="number"]');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            this.style.transform = 'scale(1.05)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    });

    // ============================================================
    // 5. ADD TO CART SUCCESS ANIMATION
    // ============================================================
    const addToCartForms = document.querySelectorAll('.actions form, form[action="cart.php"]');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button');
            if (button) {
                const originalText = button.innerHTML;
                button.innerHTML = '✓ Added!';
                button.style.background = 'var(--ubuntu)';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                }, 1500);
            }
        });
    });

    // ============================================================
    // 6. SCROLL REVEAL ANIMATION
    // ============================================================
    const revealElements = document.querySelectorAll('.stat, .product-card, .order-card, .seller-card');
    
    const revealOnScroll = function() {
        revealElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('revealed');
            }
        });
    };
    
    // Add CSS for scroll reveal
    const style = document.createElement('style');
    style.textContent = `
        .stat, .product-card, .order-card, .seller-card {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .stat.revealed, .product-card.revealed, .order-card.revealed, .seller-card.revealed {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
    
    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll();

    // ============================================================
    // 7. NOTIFICATION BELL PULSE ANIMATION
    // ============================================================
    const notificationBell = document.querySelector('a[href*="notifications.php"]');
    if (notificationBell) {
        const badge = notificationBell.querySelector('span');
        if (badge && parseInt(badge.innerText) > 0) {
            setInterval(() => {
                badge.style.animation = 'pulse 0.5s ease';
                setTimeout(() => {
                    badge.style.animation = '';
                }, 500);
            }, 5000);
        }
    }
    
    // Add pulse animation CSS
    const pulseStyle = document.createElement('style');
    pulseStyle.textContent = `
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); background: var(--danger); }
            100% { transform: scale(1); }
        }
    `;
    document.head.appendChild(pulseStyle);

    // ============================================================
    // 8. CHECKOUT DELIVERY FEE INSTANT UPDATE (already in checkout.php, but fallback)
    // ============================================================
    const deliveryRadios = document.querySelectorAll('input[name="delivery_type"]');
    if (deliveryRadios.length > 0) {
        deliveryRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                // Visual feedback
                const deliveryOptions = document.querySelectorAll('.checkout-option');
                deliveryOptions.forEach(opt => opt.classList.remove('active-option'));
                radio.closest('.checkout-option')?.classList.add('active-option');
            });
        });
    }

    // ============================================================
    // 9. PAYMENT METHOD SELECTION ANIMATION
    // ============================================================
    const paymentCards = document.querySelectorAll('.payment-card');
    paymentCards.forEach(card => {
        card.addEventListener('click', function() {
            paymentCards.forEach(c => c.classList.remove('active-option'));
            this.classList.add('active-option');
            
            // Show payment instructions with animation
            const instructions = document.getElementById('paymentInstructions');
            if (instructions) {
                instructions.style.display = 'block';
                instructions.style.animation = 'fadeInUp 0.3s ease';
            }
        });
    });
    
    // Add fadeInUp animation
    const fadeStyle = document.createElement('style');
    fadeStyle.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(fadeStyle);

    // ============================================================
    // 10. LOADING SPINNER FOR FORMS
    // ============================================================
    const allForms = document.querySelectorAll('form');
    allForms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '⏳ Loading...';
                submitBtn.disabled = true;
                
                // Don't permanently disable - will redirect or reset on page load
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    });

    // ============================================================
    // 11. HOVER TOOLTIP FOR VERIFIED BADGES
    // ============================================================
    const verifiedBadges = document.querySelectorAll('.badge-verified');
    verifiedBadges.forEach(badge => {
        badge.setAttribute('title', 'This seller has completed identity verification');
    });
    
    const trustedBadges = document.querySelectorAll('.badge-trusted');
    trustedBadges.forEach(badge => {
        badge.setAttribute('title', 'This seller has earned community trust');
    });

    // ============================================================
    // 12. AUTO-HIDE ALERTS WITH SLIDE OUT (enhanced)
    // ============================================================
    const alerts = document.querySelectorAll('.auto-hide');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'all 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                if (alert.parentNode) alert.remove();
            }, 500);
        }, 4000);
    });

    // ============================================================
    // 13. COUNTER ANIMATION FOR STATS
    // ============================================================
    const statValues = document.querySelectorAll('.stat .value');
    
    function animateNumber(element, target) {
        let current = 0;
        const increment = target / 30;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 30);
    }
    
    // Only run if stats are visible
    const statsSection = document.querySelector('.stats, .grid .stat');
    if (statsSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const stats = document.querySelectorAll('.stat .value');
                    stats.forEach(stat => {
                        const text = stat.textContent;
                        const number = parseInt(text);
                        if (!isNaN(number) && stat.getAttribute('data-animated') !== 'true') {
                            stat.setAttribute('data-animated', 'true');
                            stat.textContent = '0';
                            animateNumber(stat, number);
                        }
                    });
                    observer.disconnect();
                }
            });
        });
        observer.observe(statsSection);
    }

    // ============================================================
    // 14. SMOOTH SCROLL TO TOP BUTTON
    // ============================================================
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.innerHTML = '↑';
    scrollTopBtn.className = 'scroll-top-btn';
    scrollTopBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    document.body.appendChild(scrollTopBtn);
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollTopBtn.style.opacity = '1';
            scrollTopBtn.style.visibility = 'visible';
        } else {
            scrollTopBtn.style.opacity = '0';
            scrollTopBtn.style.visibility = 'hidden';
        }
    });
    
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    scrollTopBtn.addEventListener('mouseenter', () => {
        scrollTopBtn.style.transform = 'translateY(-3px)';
    });
    scrollTopBtn.addEventListener('mouseleave', () => {
        scrollTopBtn.style.transform = 'translateY(0)';
    });

    // ============================================================
    // 15. PRODUCT IMAGE ZOOM ON HOVER (for product detail page)
    // ============================================================
    const productImage = document.querySelector('.pd-img');
    if (productImage) {
        const zoomLens = document.createElement('div');
        zoomLens.style.cssText = `
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            backdrop-filter: blur(2px);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
        `;
        productImage.style.position = 'relative';
        productImage.appendChild(zoomLens);
        
        productImage.addEventListener('mousemove', (e) => {
            const rect = productImage.getBoundingClientRect();
            const x = e.clientX - rect.left - 50;
            const y = e.clientY - rect.top - 50;
            zoomLens.style.left = `${x}px`;
            zoomLens.style.top = `${y}px`;
            zoomLens.style.opacity = '1';
        });
        
        productImage.addEventListener('mouseleave', () => {
            zoomLens.style.opacity = '0';
        });
    }

    console.log('Kasi2Kasi animations loaded ✨');
});