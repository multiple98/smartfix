<?php
/**
 * Mobile Navigation Fix for SmartFix
 * Include this file in your pages that need mobile-responsive navigation
 * 
 * Usage: include('includes/mobile-nav-fix.php');
 */
?>

<style>
/* Mobile Navigation Hamburger Menu */
.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    padding: 8px;
    z-index: 1001;
    background: none;
    border: none;
    position: relative;
}

.hamburger span {
    width: 25px;
    height: 3px;
    background: white;
    margin: 3px 0;
    transition: all 0.3s ease;
    border-radius: 3px;
}

.hamburger.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Mobile Navigation Overlay */
.nav-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 999;
    opacity: 0;
    transition: all 0.3s ease;
}

.nav-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile Navigation Styles */
@media (max-width: 968px) {
    header {
        padding: 1rem;
        position: relative;
    }
    
    .hamburger {
        display: flex;
    }
    
    nav {
        position: fixed;
        top: 0;
        left: -100%;
        width: 280px;
        height: 100vh;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 100px 30px 30px;
        transition: left 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 15px rgba(0,0,0,0.2);
        overflow-y: auto;
    }

    nav.active {
        left: 0;
    }

    nav a {
        width: 100% !important;
        margin: 8px 0 !important;
        padding: 15px 20px !important;
        border-radius: 10px !important;
        font-size: 16px !important;
        display: flex !important;
        align-items: center !important;
        text-decoration: none;
        color: white;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    nav a:hover {
        background: rgba(255,255,255,0.1) !important;
        border-color: rgba(255,255,255,0.2);
        transform: translateX(10px);
    }

    nav a i {
        width: 25px;
        margin-right: 15px !important;
        font-size: 18px;
        text-align: center;
    }

    /* Ensure logo stays visible */
    .logo {
        font-size: 22px !important;
        z-index: 1002;
    }
    
    /* Fix header flex layout */
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
}

@media (max-width: 480px) {
    nav {
        width: 260px;
        padding: 80px 25px 25px;
    }
    
    nav a {
        padding: 12px 15px !important;
        font-size: 15px !important;
    }
    
    nav a i {
        width: 20px;
        font-size: 16px;
        margin-right: 12px !important;
    }
}

/* Additional mobile fixes */
@media (max-width: 768px) {
    body {
        overflow-x: hidden;
    }
    
    body.nav-open {
        overflow: hidden;
    }
    
    /* Ensure proper mobile scrolling */
    .page-header h1 {
        font-size: 28px !important;
    }
    
    .page-header p {
        font-size: 16px !important;
    }
    
    .form-container {
        padding: 30px 20px !important;
    }
    
    .form-row {
        flex-direction: column !important;
        gap: 0 !important;
    }
    
    .form-col {
        min-width: auto !important;
    }
}
</style>

<script>
// Mobile Navigation JavaScript
function toggleMobileNav() {
    const nav = document.querySelector('nav');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.nav-overlay');
    const body = document.body;
    
    if (nav && hamburger) {
        const isActive = nav.classList.contains('active');
        
        nav.classList.toggle('active');
        hamburger.classList.toggle('active');
        
        if (overlay) {
            overlay.classList.toggle('active');
        }
        
        // Prevent body scroll when menu is open
        if (isActive) {
            body.classList.remove('nav-open');
            body.style.overflow = 'auto';
        } else {
            body.classList.add('nav-open');
            body.style.overflow = 'hidden';
        }
    }
}

// Close mobile menu when clicking overlay or links
document.addEventListener('DOMContentLoaded', function() {
    // Close menu when clicking on overlay
    const overlay = document.querySelector('.nav-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeMobileNav);
    }
    
    // Close menu when clicking on navigation links
    const navLinks = document.querySelectorAll('nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });
    
    // Close menu when pressing escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileNav();
        }
    });
    
    // Close menu on window resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 968) {
            closeMobileNav();
        }
    });
});

function closeMobileNav() {
    const nav = document.querySelector('nav');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.nav-overlay');
    const body = document.body;
    
    if (nav && nav.classList.contains('active')) {
        nav.classList.remove('active');
        hamburger.classList.remove('active');
        
        if (overlay) {
            overlay.classList.remove('active');
        }
        
        body.classList.remove('nav-open');
        body.style.overflow = 'auto';
    }
}

// Smooth scrolling for anchor links (optional enhancement)
document.addEventListener('DOMContentLoaded', function() {
    const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
    smoothScrollLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    closeMobileNav();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
});
</script>