/**
 * GPS Location Manager for SmartFix
 * Handles location detection and mapping functionality
 */

class GPSLocationManager {
    constructor(options = {}) {
        this.options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000, // 5 minutes
            ...options
        };
        this.currentLocation = null;
        this.map = null;
        this.markers = [];
        this.isLocationDetected = false;
    }

    /**
     * Get current location
     */
    getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by this browser'));
                return;
            }

            // Show loading indicator
            this.showLocationStatus('Detecting your location...', 'loading');

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentLocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: new Date()
                    };
                    
                    this.isLocationDetected = true;
                    this.showLocationStatus('✓ Location detected successfully', 'success');
                    this.updateLocationDisplay();
                    
                    resolve(this.currentLocation);
                },
                (error) => {
                    let errorMessage = 'Unable to detect location';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = 'Location access denied by user';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = 'Location information unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMessage = 'Location detection timed out';
                            break;
                    }
                    
                    this.showLocationStatus(errorMessage, 'error');
                    reject(new Error(errorMessage));
                },
                this.options
            );
        });
    }

    /**
     * Watch position changes (for technician tracking)
     */
    watchPosition(callback) {
        if (!navigator.geolocation) {
            throw new Error('Geolocation is not supported');
        }

        return navigator.geolocation.watchPosition(
            (position) => {
                this.currentLocation = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    timestamp: new Date()
                };
                
                if (callback) callback(this.currentLocation);
            },
            (error) => {
                console.error('Location watch error:', error);
            },
            this.options
        );
    }

    /**
     * Initialize map
     */
    initializeMap(elementId, centerLat = -1.9441, centerLng = 30.0619) {
        if (typeof google === 'undefined') {
            console.error('Google Maps API not loaded');
            return false;
        }

        const mapElement = document.getElementById(elementId);
        if (!mapElement) {
            console.error('Map element not found:', elementId);
            return false;
        }

        this.map = new google.maps.Map(mapElement, {
            zoom: 13,
            center: { lat: centerLat, lng: centerLng },
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            styles: [
                {
                    featureType: 'poi',
                    elementType: 'labels',
                    stylers: [{ visibility: 'off' }]
                }
            ]
        });

        return true;
    }

    /**
     * Add marker to map
     */
    addMarker(lat, lng, title, icon = null, info = null) {
        if (!this.map) {
            console.error('Map not initialized');
            return null;
        }

        const marker = new google.maps.Marker({
            position: { lat: parseFloat(lat), lng: parseFloat(lng) },
            map: this.map,
            title: title,
            icon: icon
        });

        if (info) {
            const infoWindow = new google.maps.InfoWindow({
                content: info
            });

            marker.addListener('click', () => {
                // Close other info windows
                this.markers.forEach(m => {
                    if (m.infoWindow) m.infoWindow.close();
                });
                
                infoWindow.open(this.map, marker);
            });

            marker.infoWindow = infoWindow;
        }

        this.markers.push(marker);
        return marker;
    }

    /**
     * Show technicians on map
     */
    showTechniciansOnMap(technicians) {
        if (!this.map) return;

        // Clear existing technician markers
        this.clearMarkers('technician');

        technicians.forEach(tech => {
            const statusIcon = this.getTechnicianIcon(tech.status);
            const infoContent = `
                <div class="technician-info">
                    <h4>${tech.name}</h4>
                    <p><strong>Specialization:</strong> ${tech.specialization}</p>
                    <p><strong>Rating:</strong> ${'⭐'.repeat(Math.round(tech.rating))}</p>
                    <p><strong>Distance:</strong> ${parseFloat(tech.distance_km).toFixed(1)} km</p>
                    <p><strong>Status:</strong> <span class="status-${tech.status}">${tech.status.replace('_', ' ')}</span></p>
                    <p><strong>Phone:</strong> <a href="tel:${tech.phone}">${tech.phone}</a></p>
                    <button onclick="assignTechnician(${tech.id})" class="btn-assign">Assign</button>
                </div>
            `;

            const marker = this.addMarker(
                tech.latitude,
                tech.longitude,
                tech.name,
                statusIcon,
                infoContent
            );

            marker.type = 'technician';
            marker.technicianId = tech.id;
        });
    }

    /**
     * Get technician status icon
     */
    getTechnicianIcon(status) {
        const icons = {
            online: {
                url: 'data:image/svg+xml;base64,' + btoa(`
                    <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="12" fill="#28a745"/>
                        <circle cx="16" cy="16" r="8" fill="white"/>
                        <circle cx="16" cy="16" r="4" fill="#28a745"/>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 32),
                anchor: new google.maps.Point(16, 16)
            },
            recently_active: {
                url: 'data:image/svg+xml;base64,' + btoa(`
                    <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="12" fill="#ffc107"/>
                        <circle cx="16" cy="16" r="8" fill="white"/>
                        <circle cx="16" cy="16" r="4" fill="#ffc107"/>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 32),
                anchor: new google.maps.Point(16, 16)
            },
            offline: {
                url: 'data:image/svg+xml;base64=' + btoa(`
                    <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="12" fill="#6c757d"/>
                        <circle cx="16" cy="16" r="8" fill="white"/>
                        <circle cx="16" cy="16" r="4" fill="#6c757d"/>
                    </svg>
                `),
                scaledSize: new google.maps.Size(32, 32),
                anchor: new google.maps.Point(16, 16)
            }
        };

        return icons[status] || icons.offline;
    }

    /**
     * Clear markers by type
     */
    clearMarkers(type = null) {
        this.markers = this.markers.filter(marker => {
            if (type === null || marker.type === type) {
                marker.setMap(null);
                return false;
            }
            return true;
        });
    }

    /**
     * Center map on current location
     */
    centerOnCurrentLocation() {
        if (this.currentLocation && this.map) {
            this.map.setCenter({
                lat: this.currentLocation.latitude,
                lng: this.currentLocation.longitude
            });
            
            // Add current location marker
            this.addMarker(
                this.currentLocation.latitude,
                this.currentLocation.longitude,
                'Your Location',
                {
                    url: 'data:image/svg+xml;base64,' + btoa(`
                        <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="8" fill="#007BFF" stroke="white" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" fill="white"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(24, 24),
                    anchor: new google.maps.Point(12, 12)
                },
                '<div class="location-info"><h4>Your Current Location</h4></div>'
            );
        }
    }

    /**
     * Update location display in form
     */
    updateLocationDisplay() {
        if (!this.currentLocation) return;

        // Update hidden form fields
        const latField = document.getElementById('latitude');
        const lngField = document.getElementById('longitude');
        const accuracyField = document.getElementById('location_accuracy');

        if (latField) latField.value = this.currentLocation.latitude;
        if (lngField) lngField.value = this.currentLocation.longitude;
        if (accuracyField) accuracyField.value = this.currentLocation.accuracy;

        // Show location info
        const locationInfo = document.getElementById('location-info');
        if (locationInfo) {
            locationInfo.innerHTML = `
                <div class="location-detected">
                    <strong>📍 Location Detected</strong><br>
                    <small>Lat: ${this.currentLocation.latitude.toFixed(6)}, 
                    Lng: ${this.currentLocation.longitude.toFixed(6)}<br>
                    Accuracy: ±${Math.round(this.currentLocation.accuracy)}m</small>
                </div>
            `;
            locationInfo.style.display = 'block';
        }

        // Update address field if available
        this.reverseGeocode(this.currentLocation.latitude, this.currentLocation.longitude);
    }

    /**
     * Reverse geocoding to get address
     */
    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(`/smartfix/api/reverse-geocode.php?lat=${lat}&lng=${lng}`);
            const data = await response.json();
            
            if (data.success && data.address) {
                const addressField = document.getElementById('address');
                if (addressField && !addressField.value) {
                    addressField.value = data.address;
                }

                // Update location info with address
                const locationInfo = document.getElementById('location-info');
                if (locationInfo) {
                    const currentContent = locationInfo.innerHTML;
                    locationInfo.innerHTML = currentContent + `<br><small><strong>Address:</strong> ${data.address}</small>`;
                }
            }
        } catch (error) {
            console.error('Reverse geocoding error:', error);
        }
    }

    /**
     * Show location status
     */
    showLocationStatus(message, type = 'info') {
        const statusElement = document.getElementById('location-status');
        if (statusElement) {
            statusElement.className = `location-status ${type}`;
            statusElement.innerHTML = message;
            statusElement.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    statusElement.style.display = 'none';
                }, 3000);
            }
        }
    }

    /**
     * Calculate distance between two points
     */
    static calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    /**
     * Find nearest technicians
     */
    async findNearestTechnicians(serviceType = null) {
        if (!this.currentLocation) {
            throw new Error('Current location not detected');
        }

        try {
            const params = new URLSearchParams({
                lat: this.currentLocation.latitude,
                lng: this.currentLocation.longitude,
                service_type: serviceType || '',
                radius: 50,
                limit: 10
            });

            const response = await fetch(`/smartfix/api/find-technicians.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                return data.technicians;
            } else {
                throw new Error(data.error || 'Failed to find technicians');
            }
        } catch (error) {
            console.error('Find technicians error:', error);
            throw error;
        }
    }
}

// Global GPS manager instance
window.gpsManager = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize GPS manager
    window.gpsManager = new GPSLocationManager();

    // Auto-detect location for service request forms
    const detectLocationBtn = document.getElementById('detect-location-btn');
    if (detectLocationBtn) {
        detectLocationBtn.addEventListener('click', async function() {
            try {
                await window.gpsManager.getCurrentLocation();
                this.style.display = 'none';
                
                // Show nearest technicians if service type is selected
                const serviceType = document.querySelector('select[name="service_type"]')?.value;
                if (serviceType) {
                    showNearestTechnicians(serviceType);
                }
                
            } catch (error) {
                console.error('Location detection failed:', error);
            }
        });
    }

    // Auto-detect location on page load for certain pages
    if (document.querySelector('.auto-detect-location')) {
        window.gpsManager.getCurrentLocation().catch(console.error);
    }
});

/**
 * Show nearest technicians
 */
async function showNearestTechnicians(serviceType) {
    if (!window.gpsManager.isLocationDetected) {
        return;
    }

    try {
        const technicians = await window.gpsManager.findNearestTechnicians(serviceType);
        
        const techniciansList = document.getElementById('nearest-technicians');
        if (techniciansList && technicians.length > 0) {
            let html = '<h4>📍 Nearest Available Technicians:</h4><div class="technicians-grid">';
            
            technicians.forEach(tech => {
                html += `
                    <div class="technician-card ${tech.status}">
                        <h5>${tech.name}</h5>
                        <p class="specialization">${tech.specialization}</p>
                        <p class="rating">${'⭐'.repeat(Math.round(tech.rating))} (${tech.rating})</p>
                        <p class="distance">📍 ${parseFloat(tech.distance_km).toFixed(1)} km away</p>
                        <p class="status status-${tech.status}">${tech.status.replace('_', ' ')}</p>
                        <p class="contact">📞 <a href="tel:${tech.phone}">${tech.phone}</a></p>
                    </div>
                `;
            });
            
            html += '</div>';
            techniciansList.innerHTML = html;
            techniciansList.style.display = 'block';
        }
    } catch (error) {
        console.error('Failed to load nearest technicians:', error);
    }
}

/**
 * Assign technician (called from map info windows)
 */
function assignTechnician(technicianId) {
    const technicianField = document.getElementById('preferred_technician_id');
    if (technicianField) {
        technicianField.value = technicianId;
        
        // Show confirmation
        const statusElement = document.getElementById('location-status');
        if (statusElement) {
            statusElement.className = 'location-status success';
            statusElement.innerHTML = '✓ Technician preference saved';
            statusElement.style.display = 'block';
            setTimeout(() => statusElement.style.display = 'none', 3000);
        }
    }
}