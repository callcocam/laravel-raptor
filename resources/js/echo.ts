import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo
  }
}

/**
 * Laravel Echo setup for real-time broadcasting
 *
 * Uses Socket.IO with Redis broadcaster by default.
 * Make sure to set BROADCAST_CONNECTION=redis in your .env file
 * and run the queue worker to broadcast events.
 */

// Make Pusher available globally for Echo
window.Pusher = Pusher

// Create Echo instance
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key',
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
  wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
  wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
  wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
  forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
  disableStats: true,

  // Authorization endpoint for private/presence channels
  authEndpoint: '/broadcasting/auth',

  // Include CSRF token and credentials
  auth: {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  },
})

// Export the Echo instance
export default window.Echo

/**
 * Usage examples:
 *
 * // Listen to a channel event
 * Echo.private(`App.Models.User.${userId}`)
 *   .listen('.file-upload.processed', (e) => {
 *     console.log('File processed:', e)
 *   })
 *
 * // Leave a channel
 * Echo.leave(`App.Models.User.${userId}`)
 *
 * // Listen to a public channel
 * Echo.channel('public-channel')
 *   .listen('.PublicEvent', (e) => {
 *     console.log(e)
 *   })
 */
