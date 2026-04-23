document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('admin-login-form');
    const emailInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const errorContainer = document.getElementById('error-message');
    const submitBtn = document.querySelector('.btn-primary');
    const btnText = document.querySelector('.btn-text');
    const loader = document.querySelector('.loader');

    let csrfToken = '';

    // API Base URL - since admin_API and admin_folder are parallel, we adjust relative paths depending on deployment.
    // Assuming local server root is mapping correctly, or we can use relative.
    const apiBaseUrl = '../admin_API'; 

    // Fetch CSRF Token on load
    const fetchCsrfToken = async () => {
        try {
            const response = await fetch(`${apiBaseUrl}/csrf.php`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (data.status === 'success') {
                csrfToken = data.csrf_token;
            }
        } catch (error) {
            console.error('Failed to fetch CSRF token:', error);
        }
    };

    fetchCsrfToken();

    const showError = (message) => {
        errorContainer.textContent = message;
        errorContainer.classList.add('visible');
    };

    const hideError = () => {
        errorContainer.classList.remove('visible');
    };

    const setLoading = (isLoading) => {
        if (isLoading) {
            btnText.style.display = 'none';
            loader.style.display = 'block';
            submitBtn.disabled = true;
        } else {
            btnText.style.display = 'block';
            loader.style.display = 'none';
            submitBtn.disabled = false;
        }
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();
        
        const username = emailInput.value.trim();
        const password = passwordInput.value;

        if (!username || !password) {
            showError('Please enter both username and password');
            return;
        }

        if (!csrfToken) {
            showError('Security token missing. Please refresh the page.');
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(`${apiBaseUrl}/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    username,
                    password
                })
            });

            const data = await response.json();

            if (response.ok && data.status === 'success') {
                // Add micro-interaction for success
                submitBtn.style.background = '#10b981'; // Success green
                submitBtn.innerHTML = '<svg style="width:24px;height:24px;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                
                setTimeout(() => {
                    // Redirect to dashboard
                    window.location.href = data.redirect;
                }, 800);
            } else {
                showError(data.message || 'Authentication failed');
                setLoading(false);
                // Shake animation on error
                form.style.animation = 'shake 0.5s';
                setTimeout(() => form.style.animation = '', 500);
            }
        } catch (error) {
            console.error('Login error:', error);
            showError('Cannot connect to server. Please try again later.');
            setLoading(false);
        }
    });

    // Add inline keyframes for shake animation
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
});
