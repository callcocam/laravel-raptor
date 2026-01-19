import { configureEcho } from '@laravel/echo-vue'

/**
 * Laravel Echo Vue setup for real-time broadcasting
 * 
 * Uses @laravel/echo-vue package with Laravel Reverb
 * 
 * Documentation: https://github.com/laravel/echo-vue
 * 
 * Usage in components:
 * 
 * import { useEcho, useConnectionStatus } from '@laravel/echo-vue'
 * 
 * // Listen to a private channel
 * const { leaveChannel, stopListening, listen } = useEcho(
 *   `orders.${orderId}`,
 *   'OrderShipmentStatusUpdated',
 *   (e) => console.log(e.order)
 * )
 * 
 * // Check connection status (reactive)
 * const status = useConnectionStatus() // 'connected', 'connecting', 'reconnecting', 'failed', 'disconnected'
 */

configureEcho({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
  
  // Authorization endpoint for private/presence channels
  authEndpoint: '/broadcasting/auth',
  
  // Include CSRF token and credentials
  auth: {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  },
})
