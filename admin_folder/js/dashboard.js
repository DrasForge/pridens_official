document.addEventListener('DOMContentLoaded', () => {
    // Mobile Sidebar Toggle (Open button is handled in sidebar-module.js)
    const sidebar = document.getElementById('sidebar');
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (sidebar && window.innerWidth <= 900) {
            const openBtn = document.getElementById('open-sidebar');
            if (!sidebar.contains(e.target) && (openBtn && !openBtn.contains(e.target)) && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        }
    });

    // Logout Functionality (Handled in sidebar-module.js, but keeping here as fallback for non-sidebar pages)
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            // Perform logout process, e.g. calling an endpoint
            // For now, redirect to login page
            window.location.href = 'index.html';
        });
    }

    // Fetch Dashboard Stats
    const fetchStats = async () => {
        try {
            // Need to fetch stats from the local API
            const apiBaseUrl = '../admin_API';
            
            // First we need to get the CSRF token in case it's needed, 
            // but reading an API usually only needs cookies. For safety:
            const response = await fetch(`${apiBaseUrl}/dashboard_stats.php`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.status === 401) {
                // Not logged in
                window.location.href = 'index.html';
                return;
            }

            const data = await response.json();

            if (data.status === 'success') {
                if (document.getElementById('stat-subscribers')) animateValue("stat-subscribers", 0, data.data.total_active_subscribers, 1500);
                if (document.getElementById('stat-agents')) animateValue("stat-agents", 0, data.data.total_active_agents, 1500);
                if (document.getElementById('stat-applications')) animateValue("stat-applications", 0, data.data.total_subscribers_applications, 1500);
            }

        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
            const setErr = (id) => { const el = document.getElementById(id); if (el) el.innerText = 'Error'; };
            setErr('stat-subscribers');
            setErr('stat-agents');
            setErr('stat-applications');
        }
    };

    // Animated number counter
    function animateValue(id, start, end, duration) {
        if (start === end) return;
        const obj = document.getElementById(id);
        const range = end - start;
        let startTimestamp = null;
        
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = Math.floor(progress * range + start).toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = end.toLocaleString();
            }
        };
        window.requestAnimationFrame(step);
    }

    // Initialize fetching
    fetchStats();
});
