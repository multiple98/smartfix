<?php
/**
 * Mobile-Responsive Header for SmartFix
 * 
 * Usage: 
 * $current_page = 'services'; // Set current page for active state
 * include('includes/mobile-header.php');
 */

// Define current page for active navigation state
if (!isset($current_page)) {
    $current_page = '';
}
?>

<div class="nav-overlay" onclick="toggleMobileNav()"></div>

<header class="mobile-responsive-header">
    <div class="header-container">
        <div class="logo">
            <a href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/services/') !== false || 
                            strpos($_SERVER['REQUEST_URI'], '/user/') !== false || 
                            strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>index.php">
                <i class="fas fa-tools"></i>
                <span>SmartFixZed</span>
            </a>
        </div>
        
        <div class="hamburger" onclick="toggleMobileNav()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <nav id="mobileNav" class="main-navigation">
            <?php
            $base_url = (strpos($_SERVER['REQUEST_URI'], '/services/') !== false || 
                        strpos($_SERVER['REQUEST_URI'], '/user/') !== false || 
                        strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : '';
            ?>
            
            <a href="<?php echo $base_url; ?>index.php" <?php echo ($current_page == 'home') ? 'class="active"' : ''; ?>>
                <i class="fas fa-home"></i> Home
            </a>
            
            <a href="<?php echo $base_url; ?>services.php" <?php echo ($current_page == 'services') ? 'class="active"' : ''; ?>>
                <i class="fas fa-tools"></i> Services
            </a>
            
            <a href="<?php echo $base_url; ?>shop.php" <?php echo ($current_page == 'shop') ? 'class="active"' : ''; ?>>
                <i class="fas fa-shopping-cart"></i> Shop
            </a>
            
            <a href="<?php echo $base_url; ?>about.php" <?php echo ($current_page == 'about') ? 'class="active"' : ''; ?>>
                <i class="fas fa-info-circle"></i> About
            </a>
            
            <a href="<?php echo $base_url; ?>contact.php" <?php echo ($current_page == 'contact') ? 'class="active"' : ''; ?>>
                <i class="fas fa-phone"></i> Contact
            </a>
            
            <div class="nav-divider"></div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_url; ?>dashboard.php" <?php echo ($current_page == 'dashboard') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                
                <a href="<?php echo $base_url; ?>user/profile.php" <?php echo ($current_page == 'profile') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-user-edit"></i> My Profile
                </a>
                
                <a href="<?php echo $base_url; ?>logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>auth.php?form=login" <?php echo ($current_page == 'login') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                
                <a href="<?php echo $base_url; ?>auth.php?form=register" <?php echo ($current_page == 'register') ? 'class="active"' : ''; ?>>
                    <i class="fas fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<style>
/* Professional Mobile-Responsive Header */
.mobile-responsive-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 100;
}

.mobile-responsive-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
}

.logo {
    z-index: 1001;
}

.logo a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: white;
    font-size: 28px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.logo a:hover {
    transform: scale(1.05);
}

.logo i {
    margin-right: 10px;
    font-size: 32px;
}

.logo span {
    display: inline-block;
}

.hamburger {
    display: none;
    flex-direction: column;
    cursor: pointer;
    padding: 8px;
    z-index: 1001;
    background: none;
    border: none;
    transition: all 0.3s ease;
}

.hamburger:hover {
    transform: scale(1.1);
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

.main-navigation {
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 1;
}

.main-navigation a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    padding: 10px 16px;
    border-radius: 25px;
    position: relative;
    display: flex;
    align-items: center;
}

.main-navigation a:hover {
    color: #ffd700;
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.main-navigation a.active {
    background: rgba(255,215,0,0.2);
    color: #ffd700;
    font-weight: 600;
}

.main-navigation a.logout-link {
    background: rgba(220,53,69,0.2);
    color: #ffcdd2;
    margin-left: 10px;
}

.main-navigation a.logout-link:hover {
    background: rgba(220,53,69,0.3);
    color: white;
}

.main-navigation a i {
    margin-right: 8px;
    font-size: 16px;
}

.nav-divider {
    width: 1px;
    height: 30px;
    background: rgba(255,255,255,0.2);
    margin: 0 10px;
}

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

/* Mobile Styles */
@media (max-width: 968px) {
    .header-container {
        padding: 1rem;
    }
    
    .hamburger {
        display: flex;
    }
    
    .main-navigation {
        position: fixed;
        top: 0;
        left: -100%;
        width: 300px;
        height: 100vh;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        flex-direction: column;
        align-items: flex-start;
        padding: 100px 30px 30px;
        transition: left 0.3s ease;
        z-index: 1000;
        box-shadow: 2px 0 15px rgba(0,0,0,0.3);
        overflow-y: auto;
        gap: 5px;
    }

    .main-navigation.active {
        left: 0;
    }

    .main-navigation a {
        width: 100%;
        margin: 8px 0;
        padding: 15px 20px;
        border-radius: 10px;
        font-size: 16px;
        display: flex;
        align-items: center;
        border: 1px solid transparent;
    }

    .main-navigation a:hover {
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.2);
        transform: translateX(10px);
        color: white;
    }

    .main-navigation a.active {
        background: rgba(255,215,0,0.3);
        border-color: rgba(255,215,0,0.4);
        color: #ffd700;
    }

    .main-navigation a.logout-link {
        margin-top: 20px;
        background: rgba(220,53,69,0.3);
        border-color: rgba(220,53,69,0.4);
    }

    .main-navigation a i {
        width: 25px;
        margin-right: 15px;
        font-size: 18px;
        text-align: center;
    }

    .nav-divider {
        width: 80%;
        height: 1px;
        margin: 15px 0;
        background: rgba(255,255,255,0.2);
    }

    .logo a {
        font-size: 24px;
    }

    .logo i {
        font-size: 28px;
    }

    /* Hide text on very small screens */
    @media (max-width: 480px) {
        .logo span {
            display: none;
        }
        
        .logo i {
            margin-right: 0;
        }
        
        .main-navigation {
            width: 280px;
            padding: 80px 25px 25px;
        }
    }
}

/* Ensure body doesn't scroll when nav is open */
body.nav-open {
    overflow: hidden;
}
</style>

<script>
// Enhanced Mobile Navigation JavaScript
function toggleMobileNav() {
    const nav = document.getElementById('mobileNav');
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
        } else {
            body.classList.add('nav-open');
        }
    }
}

// Enhanced navigation functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close menu when clicking on overlay
    const overlay = document.querySelector('.nav-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeMobileNav);
    }
    
    // Close menu when clicking on navigation links
    const navLinks = document.querySelectorAll('.main-navigation a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Don't close immediately for logout link
            if (!link.classList.contains('logout-link')) {
                closeMobileNav();
            }
        });
    });
    
    // Close menu when pressing escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileNav();
        }
    });
    
    // Close menu on window resize if desktop
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (window.innerWidth > 968) {
                closeMobileNav();
            }
        }, 100);
    });
    
    // Add smooth scrolling and active state management
    setupNavigationEnhancements();
});

function closeMobileNav() {
    const nav = document.getElementById('mobileNav');
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
    }
}

function setupNavigationEnhancements() {
    // Add loading states for navigation links
    const navLinks = document.querySelectorAll('.main-navigation a:not(.logout-link)');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state
            this.style.opacity = '0.7';
            this.style.pointerEvents = 'none';
            
            // Create loading spinner
            const spinner = document.createElement('i');
            spinner.className = 'fas fa-spinner fa-spin';
            spinner.style.marginLeft = '8px';
            this.appendChild(spinner);
            
            // Remove loading state after navigation (fallback)
            setTimeout(() => {
                this.style.opacity = '1';
                this.style.pointerEvents = 'auto';
                if (spinner.parentNode) {
                    spinner.parentNode.removeChild(spinner);
                }
            }, 1000);
        });
    });
}

// Add keyboard navigation support
document.addEventListener('keydown', function(e) {
    const nav = document.getElementById('mobileNav');
    if (nav && nav.classList.contains('active')) {
        const focusableElements = nav.querySelectorAll('a');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    }
});
</script>