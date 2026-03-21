document.addEventListener('DOMContentLoaded', () => {
    // Determine the base path by finding how many directories deep we are relative to the root
    // This works better for WAMP environments where the project is not at the domain root
    let basePath = '';
    const pathSegments = window.location.pathname.split('/').filter(p => p);
    
    // Find the project root index if we are running in a subdirectory like 'Infotess-ver'
    // Assuming the project root contains 'index.html' or is the root of the domain
    // A simpler approach for this specific project structure:
    if (window.location.pathname.includes('/student/') || window.location.pathname.includes('/admin/')) {
        basePath = '../';
    } else {
        basePath = './';
    }
    
    // Load Header
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
        fetch(basePath + 'components/header.html')
            .then(res => {
                if (!res.ok) throw new Error('Header not found');
                return res.text();
            })
            .then(data => {
                headerPlaceholder.innerHTML = data;
                initializeHeader(basePath);
            })
            .catch(err => {
                console.error('Error loading header:', err);
                // Fallback for when basePath might be wrong in certain subdirectories
                if (basePath === './') {
                    fetch('../components/header.html')
                        .then(res => res.text())
                        .then(data => {
                            headerPlaceholder.innerHTML = data;
                            initializeHeader('../');
                        })
                        .catch(e => console.error('Fallback header load failed', e));
                }
            });
    }

    // Load Footer
    const footerPlaceholder = document.getElementById('footer-placeholder');
    if (footerPlaceholder) {
        fetch(basePath + 'components/footer.html')
            .then(res => {
                if (!res.ok) throw new Error('Footer not found');
                return res.text();
            })
            .then(data => {
                footerPlaceholder.innerHTML = data;
                document.getElementById('current-year').textContent = new Date().getFullYear();
            })
            .catch(err => {
                console.error('Error loading footer:', err);
                if (basePath === './') {
                    fetch('../components/footer.html')
                        .then(res => res.text())
                        .then(data => {
                            footerPlaceholder.innerHTML = data;
                            document.getElementById('current-year').textContent = new Date().getFullYear();
                        })
                        .catch(e => console.error('Fallback footer load failed', e));
                }
            });
    }
});

function initializeHeader(basePath = './') {
    // Fix image paths in header if we are in a subdirectory
    if (basePath === '../') {
        const logoImg = document.querySelector('.navbar .logo img');
        if (logoImg && logoImg.getAttribute('src').startsWith('/images/')) {
            logoImg.setAttribute('src', basePath + 'images/' + logoImg.getAttribute('src').substring(8));
        } else if (logoImg && logoImg.getAttribute('src').startsWith('images/')) {
            logoImg.setAttribute('src', basePath + logoImg.getAttribute('src'));
        }
        
        // Fix all links in the header
        const navLinks = document.querySelectorAll('.navbar a');
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.startsWith('/')) {
                link.setAttribute('href', basePath + href.substring(1));
            } else if (href && !href.startsWith('http') && !href.startsWith('#') && !href.startsWith('../')) {
                link.setAttribute('href', basePath + href);
            }
        });
    }

    const fetchWithFallback = (primaryUrl, options = {}) => {
        const u = String(primaryUrl || '');
        const fallbackUrl = u.replace(/(^|\/)(api\/)\b/, '$1backend/$2');
        
        // Add Authorization header if token exists
        const token = localStorage.getItem('infotess_token');
        if (token) {
            options.headers = {
                ...options.headers,
                'Authorization': `Bearer ${token}`
            };
        }

        return fetch(primaryUrl, options)
            .then(res => {
                if (res && res.status === 404) {
                    return fetch(fallbackUrl, options);
                }
                return res;
            })
            .catch(() => fetch(fallbackUrl, options));
    };

    // Check Auth Status - ONLY if token exists to avoid 401 console logs
    const savedToken = localStorage.getItem('infotess_token');
    if (savedToken) {
        fetchWithFallback('/api/auth/me')
            .then(res => {
                if (!res.ok) {
                    // Token might be invalid or expired, clear it
                    if (res.status === 401) {
                        localStorage.removeItem('infotess_token');
                        localStorage.removeItem('infotess_user');
                    }
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (data && data.ok && data.actor) {
                    const authLinksPlaceholder = document.getElementById('auth-links-placeholder');
                    if (authLinksPlaceholder) {
                        const role = data.actor.role || 'student';
                        let dashboardLink = basePath + 'student/dashboard.html';
                        
                        if (role === 'admin' || role === 'super_admin') {
                            dashboardLink = basePath + 'admin/dashboard.html';
                        }

                        authLinksPlaceholder.innerHTML = `
                            <a href="${dashboardLink}" class="btn-login">Dashboard</a>
                            <a href="${basePath}logout.html" style="margin-left: 10px;">Logout</a>
                        `;
                    }
                }
            })
            .catch(err => {
                // Not logged in, leave the default login link
            });
    }

    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Mobile dropdown toggle
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const dropbtn = dropdown.querySelector('.dropbtn');
        if (dropbtn) {
            dropbtn.addEventListener('click', (e) => {
                if (window.innerWidth <= 992) {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                }
            });
        }
    });
}
