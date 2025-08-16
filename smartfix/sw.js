const CACHE_NAME = 'smartfix-v1.0.0';
const OFFLINE_URL = '/smartfix/offline.html';

// Files to cache for offline functionality
const CACHE_FILES = [
  '/smartfix/',
  '/smartfix/index.php',
  '/smartfix/login.php',
  '/smartfix/register.php',
  '/smartfix/about.php',
  '/smartfix/contact.php',
  '/smartfix/shop.php',
  '/smartfix/services.php',
  '/smartfix/user/dashboard.php',
  '/smartfix/offline.html',
  '/smartfix/manifest.json',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  '/smartfix/js/jquery-3.4.1.min.js',
  '/smartfix/js/bootstrap.js',
  '/smartfix/img/cover.jpg',
  '/smartfix/img/banner.jpg'
];

// Install Service Worker
self.addEventListener('install', (event) => {
  console.log('Service Worker: Installing...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Caching Files');
        return cache.addAll(CACHE_FILES);
      })
      .then(() => {
        console.log('Service Worker: Files Cached');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.log('Service Worker: Cache Error', error);
      })
  );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
  console.log('Service Worker: Activating...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('Service Worker: Clearing Old Cache');
            return caches.delete(cache);
          }
        })
      );
    }).then(() => {
      return self.clients.claim();
    })
  );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle same-origin requests
  if (url.origin === location.origin) {
    // For navigation requests
    if (request.mode === 'navigate') {
      event.respondWith(
        fetch(request)
          .then((response) => {
            // Clone the response as it can only be used once
            const responseClone = response.clone();
            
            // Cache the response
            caches.open(CACHE_NAME)
              .then((cache) => {
                cache.put(request, responseClone);
              });
            
            return response;
          })
          .catch(() => {
            // Return cached version or offline page
            return caches.match(request)
              .then((cachedResponse) => {
                return cachedResponse || caches.match(OFFLINE_URL);
              });
          })
      );
    }
    // For other requests
    else {
      event.respondWith(
        caches.match(request)
          .then((cachedResponse) => {
            // Return cached response if found
            if (cachedResponse) {
              return cachedResponse;
            }
            
            // Otherwise, fetch from network
            return fetch(request)
              .then((response) => {
                // Don't cache if not a valid response
                if (!response || response.status !== 200 || response.type !== 'basic') {
                  return response;
                }
                
                // Clone the response
                const responseClone = response.clone();
                
                // Cache the response
                caches.open(CACHE_NAME)
                  .then((cache) => {
                    cache.put(request, responseClone);
                  });
                
                return response;
              });
          })
      );
    }
  }
  // For external resources
  else {
    event.respondWith(
      caches.match(request)
        .then((cachedResponse) => {
          return cachedResponse || fetch(request);
        })
    );
  }
});

// Push Notification Event
self.addEventListener('push', (event) => {
  console.log('Push Received:', event);
  
  let data = {};
  if (event.data) {
    data = event.data.json();
  }
  
  const options = {
    body: data.body || 'New notification from SmartFix',
    icon: '/smartfix/img/icon-192x192.png',
    badge: '/smartfix/img/icon-72x72.png',
    tag: data.tag || 'smartfix-notification',
    data: {
      url: data.url || '/smartfix/',
      ...data
    },
    actions: [
      {
        action: 'view',
        title: 'View',
        icon: '/smartfix/img/icon-96x96.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss'
      }
    ],
    requireInteraction: true,
    vibrate: [200, 100, 200]
  };
  
  event.waitUntil(
    self.registration.showNotification(
      data.title || 'SmartFix Notification',
      options
    )
  );
});

// Notification Click Event
self.addEventListener('notificationclick', (event) => {
  const { notification, action } = event;
  
  if (action === 'dismiss') {
    notification.close();
    return;
  }
  
  // Default action is to open the app
  event.waitUntil(
    clients.matchAll({ type: 'window' })
      .then((clientList) => {
        // If app is already open, focus on it
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url === notification.data.url && 'focus' in client) {
            notification.close();
            return client.focus();
          }
        }
        
        // Otherwise open new window
        if (clients.openWindow) {
          notification.close();
          return clients.openWindow(notification.data.url);
        }
      })
  );
});

// Background Sync Event
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    console.log('Background Sync Event');
    event.waitUntil(
      // Perform background sync operations here
      syncOfflineData()
    );
  }
});

// Function to sync offline data
function syncOfflineData() {
  return caches.open('smartfix-offline-data')
    .then((cache) => {
      return cache.keys();
    })
    .then((requests) => {
      return Promise.all(
        requests.map((request) => {
          // Process offline data sync
          return processOfflineRequest(request);
        })
      );
    });
}

function processOfflineRequest(request) {
  // Implement offline data processing logic
  return Promise.resolve();
}