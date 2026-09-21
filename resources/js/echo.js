import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : 8083;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
});

console.log(
    'reverb working..'
);


// Helper to get dynamic values from URL query parameters, window properties, HTML DOM elements, localStorage, or defaults
const getDynamicParam = (key, defaultValue) => {
    const camelKey = key.replace(/_([a-z])/g, (g) => g[1].toUpperCase());
    
    // 1. Get from URL search or hash query string (handles SPA / frontend routing)
    const getQueryString = () => {
        if (window.location.search) return window.location.search;
        const hash = window.location.hash;
        const index = hash.indexOf('?');
        return index !== -1 ? hash.substring(index) : '';
    };
    const urlParams = new URLSearchParams(getQueryString());
    const urlVal = urlParams.get(key) || urlParams.get(camelKey);
    if (urlVal !== null && urlVal !== '') return urlVal;

    // 2. Get from global window variables
    if (window[key] !== undefined && window[key] !== null) return window[key];
    if (window[camelKey] !== undefined && window[camelKey] !== null) return window[camelKey];

    // 3. Get from HTML DOM elements (inputs/meta tags/elements with id)
    const domElement = document.getElementById(key) || 
                       document.getElementById(camelKey) || 
                       document.querySelector(`input[name="${key}"]`) || 
                       document.querySelector(`input[name="${camelKey}"]`) ||
                       document.querySelector(`meta[name="${key}"]`) ||
                       document.querySelector(`meta[name="${camelKey}"]`);
                       
    if (domElement) {
        const val = domElement.value || domElement.getAttribute('content');
        if (val !== null && val !== '') return val;
    }

    // 4. Get from localStorage
    const localVal = localStorage.getItem(key) || localStorage.getItem(camelKey);
    if (localVal !== null && localVal !== '') return localVal;

    // 5. Fallback to default value
    return defaultValue;
};

// ================= DEFERRED INITIALIZATION =================
const initEchoListeners = () => {
    // ================= DYNAMIC PARAMETERS =================
    const orderId = getDynamicParam('order_id', 1);
    const customerId = getDynamicParam('customer_id', 2);
    const vendorId = getDynamicParam('vendor_id', 3);
    const rideId = getDynamicParam('ride_id', 5);
    const deliveryManId = getDynamicParam('delivery_man_id', 5);
    const riderId = getDynamicParam('rider_id', 4);

    console.log('Echo initialized with dynamic parameters:', {
        orderId,
        customerId,
        vendorId,
        rideId,
        deliveryManId,
        riderId
    });

    // ================= ORDER LOCATION LISTEN =================
    window.Echo.channel('order.' + orderId)
        .listen('.order.location.updated', (event) => {
            console.log('Order Location Updated:', event);
        })
        .listen('.order.status.updated', (event) => {
            console.log('Order Status Updated:', event);
        })
        .listen('.order.confirmation_code.sent', (event) => {
            console.log('Order Confirmation Code Sent:', event);
        });

    // ================= CHAT LISTEN =================
    window.Echo.channel(`order.${orderId}.chat`)
        .listen('.message.sent', (event) => {
            console.log('NEW MESSAGE RECEIVED:', event);
        })
        .listen('.messages.read', (event) => {
            console.log('MESSAGES READ BY:', event.reader_id);
        });

    // ================= RIDE CHAT LISTEN =================
    window.Echo.channel(`ride.${rideId}.chat`)
        .listen('.message.sent', (event) => {
            console.log('NEW RIDE MESSAGE RECEIVED:', event);
        })
        .listen('.messages.read', (event) => {
            console.log('RIDE MESSAGES READ BY:', event.reader_id);
        });

    // ================= RIDE LOCATION LISTEN =================
    window.Echo.channel(`ride.${rideId}`)
        .listen('.ride.location.updated', (event) => {
            console.log('Ride Location Updated:', event);
        })
        .listen('.ride.status.updated', (event) => {
            console.log('Ride Status Updated:', event);
        });

    // ================= SUPPORT CHAT LISTEN =================
    window.Echo.channel(`support.${customerId}`)
        .listen('.support.message.sent', (event) => {
            console.log('NEW SUPPORT MESSAGE:', event);
        });

    // ================= VENDOR NEW ORDER LISTEN =================
    window.Echo.channel(`vendor.${vendorId}`)
        .listen('.order.placed', (event) => {
            console.log('NEW ORDER PLACED (Real-time Vendor Alert):', event);
        })
        .listen('.vendor.status.updated', (event) => {
            console.log('Vendor Status Updated:', event);
        });

    // ================= DELIVERY MAN AVAILABLE ORDER LISTEN =================
    window.Echo.channel('delivery_man.available')
        .listen('.order.ready', (event) => {
            console.log('NEW AVAILABLE ORDER READY FOR DELIVERY DRIVER:', event);
        });

    // ================= DELIVERY MAN ASSIGNED ORDER LISTEN =================
    window.Echo.channel(`delivery_man.${deliveryManId}`)
        .listen('.order.ready', (event) => {
            console.log('YOUR ASSIGNED ORDER IS READY FOR DELIVERY DRIVER:', event);
        })
        .listen('.delivery_man.status.updated', (event) => {
            console.log('Delivery Driver Status Updated:', event);
        });

    // ================= RIDER AVAILABLE RIDE LISTEN =================
    window.Echo.channel('rider.available')
        .listen('.ride.requested', (event) => {
            console.log('NEW RIDE REQUESTED FOR RIDER:', event);
        });

    // ================= RIDER STATUS LISTEN =================
    window.Echo.channel(`rider.${riderId}`)
        .listen('.rider.status.updated', (event) => {
            console.log('Rider Status Updated:', event);
        });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEchoListeners);
} else {
    // Already loaded, but add a slight delay to ensure any inline HTML script blocks execute first
    setTimeout(initEchoListeners, 50);
}