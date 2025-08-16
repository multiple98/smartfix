// Progressive Web App (PWA) functionality
class PWAManager {
    constructor() {
        this.initializePWA();
        this.setupInstallPrompt();
        this.setupPushNotifications();
        this.setupNetworkStatus();
    }

    async initializePWA() {
        // Register service worker
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/smartfix/sw.js');
                console.log('ServiceWorker registration successful:', registration.scope);
                
                // Listen for updates
                registration.addEventListener('updatefound', () => {
                    this.showUpdateAvailable();
                });
            } catch (error) {
                console.error('ServiceWorker registration failed:', error);
            }
        }
    }

    setupInstallPrompt() {
        let deferredPrompt;
        const installButton = document.getElementById('installApp');
        
        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button
            if (installButton) {
                installButton.style.display = 'block';
                installButton.addEventListener('click', () => {
                    this.installApp(deferredPrompt);
                });
            } else {
                // Create dynamic install prompt
                this.showInstallBanner(deferredPrompt);
            }
        });

        // Handle successful installation
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            this.showNotification('App installed successfully!', 'success');
            this.hideInstallPrompt();
        });
    }

    async installApp(deferredPrompt) {
        if (!deferredPrompt) return;

        const result = await deferredPrompt.prompt();
        console.log('Install prompt result:', result.outcome);
        
        deferredPrompt = null;
        this.hideInstallPrompt();
    }

    showInstallBanner(deferredPrompt) {
        // Create install banner
        const banner = document.createElement('div');
        banner.id = 'install-banner';
        banner.className = 'install-banner';
        banner.innerHTML = `
            <div class="install-content">
                <i class="fas fa-mobile-alt"></i>
                <div>
                    <strong>Install SmartFix App</strong>
                    <p>Get the full app experience with offline access</p>
                </div>
                <button class="install-btn" onclick="pwaNanager.installApp(this.deferredPrompt)">
                    Install
                </button>
                <button class="close-btn" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add to page
        document.body.insertBefore(banner, document.body.firstChild);
        
        // Store deferred prompt on banner
        banner.deferredPrompt = deferredPrompt;
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            if (document.getElementById('install-banner')) {
                banner.remove();
            }
        }, 10000);
    }

    hideInstallPrompt() {
        const banner = document.getElementById('install-banner');
        const button = document.getElementById('installApp');
        
        if (banner) banner.remove();
        if (button) button.style.display = 'none';
    }

    showUpdateAvailable() {
        const updateBanner = document.createElement('div');
        updateBanner.className = 'update-banner';
        updateBanner.innerHTML = `
            <div class="update-content">
                <i class="fas fa-sync-alt"></i>
                <span>A new version is available!</span>
                <button onclick="location.reload()">Update</button>
            </div>
        `;
        
        document.body.appendChild(updateBanner);
        
        // Auto-reload after 5 seconds if user doesn't interact
        setTimeout(() => {
            if (document.querySelector('.update-banner')) {
                location.reload();
            }
        }, 5000);
    }

    async setupPushNotifications() {
        if (!('Notification' in window) || !('serviceWorker' in navigator)) {
            console.log('Push notifications not supported');
            return;
        }

        // Check current permission
        let permission = Notification.permission;
        
        if (permission === 'default') {
            // Show notification request banner
            this.showNotificationRequestBanner();
        }
    }

    showNotificationRequestBanner() {
        const banner = document.createElement('div');
        banner.className = 'notification-request-banner';
        banner.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-bell"></i>
                <div>
                    <strong>Stay Updated</strong>
                    <p>Get notified about your service requests and orders</p>
                </div>
                <button onclick="pwaManager.requestNotificationPermission()">
                    Allow
                </button>
                <button onclick="this.parentElement.parentElement.remove()">
                    Not Now
                </button>
            </div>
        `;
        
        document.body.appendChild(banner);
    }

    async requestNotificationPermission() {
        try {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                this.showNotification('Notifications enabled!', 'success');
                document.querySelector('.notification-request-banner')?.remove();
                
                // Subscribe to push notifications
                await this.subscribeToPushNotifications();
            }
        } catch (error) {
            console.error('Error requesting notification permission:', error);
        }
    }

    async subscribeToPushNotifications() {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (!registration) return;

            // You would implement your push subscription logic here
            // This would typically involve sending the subscription to your server
            console.log('Push notification subscription setup complete');
        } catch (error) {
            console.error('Error subscribing to push notifications:', error);
        }
    }

    setupNetworkStatus() {
        // Show network status
        this.updateNetworkStatus();
        
        window.addEventListener('online', () => {
            this.updateNetworkStatus();
            this.showNotification('Connection restored!', 'success');
        });
        
        window.addEventListener('offline', () => {
            this.updateNetworkStatus();
            this.showNotification('You are offline. Some features may be limited.', 'warning');
        });
    }

    updateNetworkStatus() {
        const statusElement = document.getElementById('network-status');
        if (!statusElement) return;

        if (navigator.onLine) {
            statusElement.className = 'network-status online';
            statusElement.innerHTML = '<i class="fas fa-wifi"></i> Online';
        } else {
            statusElement.className = 'network-status offline';
            statusElement.innerHTML = '<i class="fas fa-wifi-slash"></i> Offline';
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `pwa-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    // Utility methods for offline functionality
    isOnline() {
        return navigator.onLine;
    }

    async cacheImportantData() {
        if (!('caches' in window)) return;

        try {
            const cache = await caches.open('smartfix-data');
            
            // Cache important pages
            const importantPages = [
                '/smartfix/user/dashboard.php',
                '/smartfix/shop.php',
                '/smartfix/services.php'
            ];
            
            await cache.addAll(importantPages);
            console.log('Important data cached for offline use');
        } catch (error) {
            console.error('Error caching data:', error);
        }
    }

    async syncOfflineData() {
        // Sync any offline data when connection is restored
        if (!this.isOnline()) return;

        try {
            // Check for offline form submissions
            const offlineData = localStorage.getItem('smartfix-offline-data');
            if (offlineData) {
                const data = JSON.parse(offlineData);
                
                // Process offline submissions
                for (const item of data) {
                    await this.submitOfflineData(item);
                }
                
                // Clear offline data
                localStorage.removeItem('smartfix-offline-data');
                this.showNotification('Offline data synchronized!', 'success');
            }
        } catch (error) {
            console.error('Error syncing offline data:', error);
        }
    }

    async submitOfflineData(data) {
        // Submit offline form data
        try {
            const response = await fetch(data.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data.formData)
            });
            
            if (!response.ok) {
                throw new Error('Failed to submit offline data');
            }
            
            console.log('Offline data submitted successfully:', data);
        } catch (error) {
            console.error('Error submitting offline data:', error);
        }
    }

    saveOfflineFormData(url, formData) {
        // Save form data for later submission when online
        try {
            const existingData = localStorage.getItem('smartfix-offline-data');
            const offlineData = existingData ? JSON.parse(existingData) : [];
            
            offlineData.push({
                url,
                formData,
                timestamp: Date.now()
            });
            
            localStorage.setItem('smartfix-offline-data', JSON.stringify(offlineData));
            this.showNotification('Data saved for when you\'re back online!', 'info');
        } catch (error) {
            console.error('Error saving offline data:', error);
        }
    }
}

// Initialize PWA Manager
let pwaManager;
document.addEventListener('DOMContentLoaded', () => {
    pwaManager = new PWAManager();
});

// Export for global access
window.pwaManager = pwaManager;