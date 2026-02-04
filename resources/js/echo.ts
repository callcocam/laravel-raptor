import { configureEcho } from "@laravel/echo-vue";

// Configura o Echo com Pusher/Soketi
// Soketi é um servidor WebSocket compatível com Pusher Protocol
// Roda na porta 6001 (não 8080 que seria do Reverb)
// IMPORTANTE: withCredentials: true é necessário quando SESSION_DRIVER=redis
// para garantir que cookies de sessão sejam enviados na autenticação

// Obtém variáveis de ambiente do Vite (build-time) ou do window (runtime)
// No build do Docker, as variáveis VITE_* são injetadas durante o build
// No runtime, se não estiverem disponíveis, usamos window (injetado via Laravel)
const getEnvVar = (key: string, otherKey: string, fallback: string): string => {
    // Tenta primeiro import.meta.env (build-time)
    if (import.meta.env[key]) {
        return import.meta.env[key];
    }
    if (import.meta.env[otherKey]) {
        return import.meta.env[otherKey];
    }
    // Se não estiver disponível, tenta window (runtime injection via Laravel)
    if (typeof window !== 'undefined' && (window as any)[key]) {
        return (window as any)[key];
    }
    if (typeof window !== 'undefined' && (window as any)[otherKey]) {
        return (window as any)[otherKey];
    }
    // Fallback
    return fallback;
};

const echoConfig = {
    broadcaster: "pusher" as const,
    // Valores para Soketi (compatível com Pusher Protocol)
    key: getEnvVar('VITE_PUSHER_APP_KEY', 'VITE_REVERB_APP_KEY', 'YWm0vycOJNEaiCMTUKUMLOT4ysb06WZd'),
    wsHost: getEnvVar('VITE_PUSHER_HOST', 'VITE_REVERB_HOST', 'localhost'),
    wsPort: Number(getEnvVar('VITE_PUSHER_PORT', 'VITE_REVERB_PORT', '6001')), // Porta padrão do Soketi
    wssPort: Number(getEnvVar('VITE_PUSHER_PORT', 'VITE_REVERB_PORT', '6001')),
    forceTLS: (getEnvVar('VITE_PUSHER_SCHEME', 'VITE_REVERB_SCHEME', 'http') === 'https'),
    cluster: getEnvVar('VITE_PUSHER_APP_CLUSTER', 'VITE_REVERB_APP_CLUSTER', 'mt1'),
    enabledTransports: ['ws', 'wss'] as ('ws' | 'wss')[],
    // Necessário para enviar cookies de sessão quando SESSION_DRIVER=redis
    withCredentials: true,
    disableStats: true, // Soketi não precisa de stats do Pusher
};

console.log('[Echo] Configuração:', echoConfig);

configureEcho(echoConfig);