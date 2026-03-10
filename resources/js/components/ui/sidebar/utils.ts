export const SIDEBAR_COOKIE_NAME = 'sidebar_state'
export const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 7
export const SIDEBAR_WIDTH = '16rem'
export const SIDEBAR_WIDTH_MOBILE = '18rem'
export const SIDEBAR_WIDTH_ICON = '3rem'
export const SIDEBAR_KEYBOARD_SHORTCUT = 'b'

// Re-export from the app's sidebar utils so both share the exact same
// Vue injection key (the Symbol created by reka-ui's createContext).
// This ensures the package's components can consume context provided by
// either the app's SidebarProvider or the package's own SidebarProvider.
export { useSidebar, provideSidebarContext } from '@/components/ui/sidebar/utils'
