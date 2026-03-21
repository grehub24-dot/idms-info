/**
 * INFOTESS API v2 Client Helper
 * Handles authentication and API communication
 */

class InfotessAPI {
    constructor(baseURL = '/api/v2') {
        this.baseURL = baseURL;
        this.token = localStorage.getItem('infotess_token');
    }

    // Store JWT token
    setToken(token) {
        this.token = token;
        localStorage.setItem('infotess_token', token);
    }

    // Remove JWT token
    clearToken() {
        this.token = null;
        localStorage.removeItem('infotess_token');
    }

    // Get auth headers
    getHeaders() {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        return headers;
    }

    // Make API request
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: this.getHeaders(),
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            // Handle token expiration
            if (response.status === 401) {
                this.clearToken();
                window.location.href = '/login.html';
                return;
            }

            // Handle API errors
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'API request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Authentication methods
    async login(email, password) {
        const data = await this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        if (data.data.token) {
            this.setToken(data.data.token);
        }

        return data.data;
    }

    async register(userData) {
        const data = await this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(userData)
        });

        if (data.data.token) {
            this.setToken(data.data.token);
        }

        return data.data;
    }

    async getCurrentUser() {
        return await this.request('/auth/me');
    }

    async logout() {
        this.clearToken();
        // Optionally call logout endpoint if exists
    }

    // User profile methods
    async getProfile() {
        return await this.request('/users/profile');
    }

    async updateProfile(profileData) {
        return await this.request('/users/profile', {
            method: 'PUT',
            body: JSON.stringify(profileData)
        });
    }

    // Health check
    async healthCheck() {
        return await this.request('/health');
    }

    // Check if user is authenticated
    isAuthenticated() {
        return !!this.token;
    }

    // Get current user info from token (basic)
    getUserFromToken() {
        if (!this.token) return null;
        
        try {
            const payload = JSON.parse(atob(this.token.split('.')[1]));
            return payload;
        } catch (error) {
            console.error('Invalid token:', error);
            this.clearToken();
            return null;
        }
    }
}

// Create global API instance
const api = new InfotessAPI();

// Utility functions for common operations
const AuthUtils = {
    // Handle login form submission
    async handleLogin(form) {
        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');

        try {
            const result = await api.login(email, password);
            
            // Show success message
            this.showMessage('Login successful!', 'success');
            
            // Redirect based on user role
            setTimeout(() => {
                const redirectUrl = result.user.role === 'admin' ? '/admin/' : '/student/';
                window.location.href = redirectUrl;
            }, 1000);
            
        } catch (error) {
            this.showMessage(error.message, 'error');
        }
    },

    // Handle registration form submission
    async handleRegister(form) {
        const formData = new FormData(form);
        const userData = {
            email: formData.get('email'),
            password: formData.get('password'),
            full_name: formData.get('full_name'),
            role: formData.get('role') || 'student'
        };

        try {
            const result = await api.register(userData);
            
            this.showMessage('Registration successful!', 'success');
            
            // Redirect to login or dashboard
            setTimeout(() => {
                window.location.href = '/student/';
            }, 1000);
            
        } catch (error) {
            this.showMessage(error.message, 'error');
        }
    },

    // Show message to user
    showMessage(message, type = 'info') {
        // Create or update message container
        let messageContainer = document.getElementById('message-container');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.id = 'message-container';
            messageContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(messageContainer);
        }

        const messageEl = document.createElement('div');
        messageEl.textContent = message;
        messageEl.style.cssText = `
            padding: 12px 16px;
            margin-bottom: 10px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            ${type === 'success' ? 'background-color: #10b981;' : ''}
            ${type === 'error' ? 'background-color: #ef4444;' : ''}
            ${type === 'info' ? 'background-color: #3b82f6;' : ''}
        `;

        messageContainer.appendChild(messageEl);

        // Auto remove after 5 seconds
        setTimeout(() => {
            messageEl.remove();
        }, 5000);
    },

    // Check authentication and redirect if needed
    checkAuth() {
        if (!api.isAuthenticated()) {
            window.location.href = '/login.html';
            return false;
        }
        return true;
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { InfotessAPI, api, AuthUtils };
}
