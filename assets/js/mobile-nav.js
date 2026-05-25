/**
 * Mobile Navigation Handler
 * Handles sidebar drawer and cart drawer for mobile devices
 */

document.addEventListener('DOMContentLoaded', function () {
    // Create mobile header if it doesn't exist
    createMobileHeader();

    // Create sidebar header mobile close button
    createSidebarMobileHeader();

    // Setup event listeners
    setupMobileNavigation();
});

function createMobileHeader() {
    // Check if mobile header already exists
    if (document.querySelector('.mobile-header')) {
        return;
    }

    const dashboardLayout = document.querySelector('.dashboard-layout');
    if (!dashboardLayout) return;

    const mobileHeader = document.createElement('div');
    mobileHeader.className = 'mobile-header';
    mobileHeader.innerHTML = `
        <button class="icon-btn" id="menuToggle" aria-label="Toggle Menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="brand-mobile">
            <i class="fas fa-mug-hot"></i> Bellen Beans
        </div>
        <button class="icon-btn" id="cartToggle" aria-label="Toggle Cart" style="display: none;">
            <i class="fas fa-shopping-cart"></i>
            <span class="badge-count" id="cartCount">0</span>
        </button>
    `;

    // Insert before dashboard layout
    dashboardLayout.parentNode.insertBefore(mobileHeader, dashboardLayout);

    // Show cart toggle only if cart sidebar exists
    const cartSidebar = document.querySelector('.cart-sidebar');
    if (cartSidebar) {
        document.getElementById('cartToggle').style.display = 'block';
    }
}

function createSidebarMobileHeader() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // Check if mobile header already exists in sidebar
    if (sidebar.querySelector('.sidebar-header-mobile')) {
        return;
    }

    const sidebarHeader = document.createElement('div');
    sidebarHeader.className = 'sidebar-header-mobile';
    sidebarHeader.innerHTML = `
        <div class="brand">
            <i class="fas fa-mug-hot"></i> Bellen Beans Coffee
        </div>
        <button class="close-sidebar" aria-label="Close Menu">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Insert at the beginning of sidebar
    sidebar.insertBefore(sidebarHeader, sidebar.firstChild);
}

function setupMobileNavigation() {
    const menuToggle = document.getElementById('menuToggle');
    const cartToggle = document.getElementById('cartToggle');
    const sidebar = document.querySelector('.sidebar');
    const cartSidebar = document.querySelector('.cart-sidebar');
    const closeSidebarBtn = document.querySelector('.close-sidebar');

    // Menu toggle
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');

            // Close cart if open
            if (cartSidebar) {
                cartSidebar.classList.remove('active');
            }
        });
    }

    // Cart toggle
    if (cartToggle && cartSidebar) {
        cartToggle.addEventListener('click', function () {
            cartSidebar.classList.toggle('active');

            // Close sidebar if open
            if (sidebar) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
    }

    // Close sidebar button
    if (closeSidebarBtn && sidebar) {
        closeSidebarBtn.addEventListener('click', function () {
            sidebar.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        });
    }

    // Close sidebar when clicking overlay
    document.addEventListener('click', function (e) {
        if (document.body.classList.contains('sidebar-open')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        }
    });

    // Close cart when clicking outside
    document.addEventListener('click', function (e) {
        if (cartSidebar && cartSidebar.classList.contains('active')) {
            if (!cartSidebar.contains(e.target) && !cartToggle.contains(e.target)) {
                cartSidebar.classList.remove('active');
            }
        }
    });

    // Update cart count badge
    updateCartCount();
}

function updateCartCount() {
    const cartItems = document.querySelectorAll('.cart-item');
    const cartCountBadge = document.getElementById('cartCount');

    if (cartCountBadge) {
        const count = cartItems.length;
        cartCountBadge.textContent = count;
        cartCountBadge.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Export function to update cart count from other scripts
window.updateMobileCartCount = updateCartCount;

// Handle window resize
let resizeTimer;
window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
        // Close sidebar and cart on resize to desktop
        if (window.innerWidth > 1024) {
            const sidebar = document.querySelector('.sidebar');
            const cartSidebar = document.querySelector('.cart-sidebar');

            if (sidebar) {
                sidebar.classList.remove('active');
            }
            if (cartSidebar) {
                cartSidebar.classList.remove('active');
            }
            document.body.classList.remove('sidebar-open');
        }
    }, 250);
});
