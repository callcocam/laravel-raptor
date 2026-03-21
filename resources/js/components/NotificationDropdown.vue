<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGlobalNotifications, type GlobalNotification } from '../composables/useGlobalNotifications';
import { cn } from '~/lib/utils';
import {
    Bell,
    CheckCheck,
    Trash2,
    Wifi,
    WifiOff,
    Loader2,
    CheckCircle2,
    XCircle,
    AlertTriangle,
    Info,
    Download,
    FileWarning,
    X,
} from 'lucide-vue-next';

const {
    notifications,
    unreadCount,
    connectionStatus,
    isConnected,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAll,
} = useGlobalNotifications();

// ── Painel aberto/fechado ────────────────────────────────────────────────────
const isOpen = ref(false);
const panelRef = ref<HTMLElement | null>(null);
const triggerRef = ref<HTMLElement | null>(null);

const toggle = () => {
    isOpen.value = !isOpen.value;
};

const close = () => {
    isOpen.value = false;
};

const handleClickOutside = (e: MouseEvent) => {
    const target = e.target as Node;
    const inPanel = panelRef.value?.contains(target);
    const inTrigger = triggerRef.value?.contains(target);
    if (!inPanel && !inTrigger) {
        isOpen.value = false;
    }
};

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});

// ── Computeds ────────────────────────────────────────────────────────────────
const hasNotifications = computed(() => notifications.value.length > 0);
const hasUnread = computed(() => unreadCount.value > 0);
const badgeCount = computed(() => (unreadCount.value > 99 ? '99+' : String(unreadCount.value)));

// ── Agrupamento por tempo ────────────────────────────────────────────────────
const groupedNotifications = computed(() => {
    const todayStart = new Date();
    todayStart.setHours(0, 0, 0, 0);

    const yesterdayStart = new Date(todayStart.getTime() - 86_400_000);

    const today: GlobalNotification[] = [];
    const yesterday: GlobalNotification[] = [];
    const older: GlobalNotification[] = [];

    for (const n of notifications.value) {
        const d = new Date(n.created_at);
        if (d >= todayStart) {
            today.push(n);
        } else if (d >= yesterdayStart) {
            yesterday.push(n);
        } else {
            older.push(n);
        }
    }

    return [
        { label: 'Hoje', items: today },
        { label: 'Ontem', items: yesterday },
        { label: 'Anteriores', items: older },
    ].filter((g) => g.items.length > 0);
});

// ── Ícone por tipo ────────────────────────────────────────────────────────────
type NotifType = 'success' | 'error' | 'warning' | 'info';

const typeConfig: Record<
    NotifType,
    { icon: typeof CheckCircle2; bg: string; text: string }
> = {
    success: {
        icon: CheckCircle2,
        bg: 'bg-emerald-500/10 dark:bg-emerald-500/15',
        text: 'text-emerald-600 dark:text-emerald-400',
    },
    error: {
        icon: XCircle,
        bg: 'bg-red-500/10 dark:bg-red-500/15',
        text: 'text-red-600 dark:text-red-400',
    },
    warning: {
        icon: AlertTriangle,
        bg: 'bg-amber-500/10 dark:bg-amber-500/15',
        text: 'text-amber-600 dark:text-amber-400',
    },
    info: {
        icon: Info,
        bg: 'bg-blue-500/10 dark:bg-blue-500/15',
        text: 'text-blue-600 dark:text-blue-400',
    },
};

const getTypeConfig = (type: string) =>
    typeConfig[type as NotifType] ?? typeConfig.info;

// ── Formatação de tempo ───────────────────────────────────────────────────────
const formatTime = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60_000);
    const hours = Math.floor(diff / 3_600_000);
    const days = Math.floor(diff / 86_400_000);

    if (minutes < 1) return 'Agora';
    if (minutes < 60) return `${minutes}m atrás`;
    if (hours < 24) return `${hours}h atrás`;
    if (days < 7) return `${days}d atrás`;
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
};

// ── Status de conexão ─────────────────────────────────────────────────────────
const connDotClass = computed(() => {
    switch (connectionStatus.value) {
        case 'connected':
            return 'bg-emerald-500';
        case 'connecting':
        case 'reconnecting':
            return 'bg-amber-400 animate-pulse';
        case 'failed':
            return 'bg-red-500';
        default:
            return 'bg-muted-foreground/40';
    }
});

const connLabel = computed(() => {
    switch (connectionStatus.value) {
        case 'connected':
            return 'Conectado';
        case 'connecting':
            return 'Conectando…';
        case 'reconnecting':
            return 'Reconectando…';
        case 'failed':
            return 'Falha na conexão';
        default:
            return 'Desconectado';
    }
});
</script>

<template>
    <div class="relative">
        <!-- ── Botão trigger ─────────────────────────────────────────────────── -->
        <button
            ref="triggerRef"
            type="button"
            :aria-label="`Notificações${hasUnread ? ` (${unreadCount} não lidas)` : ''}`"
            :aria-expanded="isOpen"
            class="relative flex size-7 items-center justify-center rounded-md text-muted-foreground/70 transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            @click="toggle"
        >
            <Bell class="size-4" />

            <!-- Badge de não lidas -->
            <span
                v-if="hasUnread"
                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-input-focus)] px-0.5 text-[9px] font-bold leading-none text-slate-900"
            >
                {{ badgeCount }}
            </span>

            <!-- Ponto de status da conexão -->
            <span
                :class="cn(
                    'absolute -bottom-0.5 -right-0.5 size-2 rounded-full border border-background',
                    connDotClass,
                )"
                :title="connLabel"
            />
        </button>

        <!-- ── Painel ────────────────────────────────────────────────────────── -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
        >
            <div
                v-if="isOpen"
                ref="panelRef"
                role="dialog"
                aria-label="Painel de notificações"
                class="absolute right-0 top-[calc(100%+8px)] z-50 w-80 origin-top-right overflow-hidden rounded-xl border border-border bg-popover shadow-xl shadow-black/10 ring-1 ring-border/40"
            >
                <!-- ── Cabeçalho ───────────────────────────────────────────── -->
                <div class="flex items-center justify-between gap-2 border-b border-border/60 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-foreground">Notificações</h3>
                        <!-- Status de conexão -->
                        <span
                            :class="cn(
                                'flex items-center gap-1 text-[10px] font-medium',
                                isConnected ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground',
                                connectionStatus === 'failed' ? 'text-destructive' : '',
                            )"
                            :title="connLabel"
                        >
                            <Loader2
                                v-if="connectionStatus === 'connecting' || connectionStatus === 'reconnecting'"
                                class="size-2.5 animate-spin"
                            />
                            <Wifi v-else-if="isConnected" class="size-2.5" />
                            <WifiOff v-else class="size-2.5" />
                            <span class="hidden sm:inline">{{ connLabel }}</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <!-- Marcar todas como lidas -->
                        <button
                            v-if="hasUnread"
                            type="button"
                            title="Marcar todas como lidas"
                            class="flex items-center gap-1 rounded px-1.5 py-1 text-[11px] font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                            @click="markAllAsRead"
                        >
                            <CheckCheck class="size-3" />
                            <span>Ler todas</span>
                        </button>

                        <!-- Limpar tudo -->
                        <button
                            v-if="hasNotifications"
                            type="button"
                            title="Limpar todas as notificações"
                            class="flex items-center gap-1 rounded px-1.5 py-1 text-[11px] font-medium text-destructive/70 transition-colors hover:bg-destructive/10 hover:text-destructive"
                            @click="clearAll"
                        >
                            <Trash2 class="size-3" />
                        </button>

                        <!-- Fechar painel -->
                        <button
                            type="button"
                            title="Fechar"
                            class="flex size-6 items-center justify-center rounded text-muted-foreground/60 transition-colors hover:bg-accent hover:text-foreground"
                            @click="close"
                        >
                            <X class="size-3.5" />
                        </button>
                    </div>
                </div>

                <!-- ── Lista de notificações ───────────────────────────────── -->
                <div class="max-h-[380px] overflow-y-auto overscroll-contain">
                    <!-- Estado vazio -->
                    <div
                        v-if="!hasNotifications"
                        class="flex flex-col items-center justify-center gap-3 px-6 py-10 text-center"
                    >
                        <div class="flex size-12 items-center justify-center rounded-full bg-muted">
                            <Bell class="size-5 text-muted-foreground/50" />
                        </div>
                        <div>
                            <p class="text-sm font-medium text-foreground">Tudo em dia!</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Nenhuma notificação por enquanto.
                            </p>
                        </div>
                    </div>

                    <!-- Grupos de notificações -->
                    <template v-else>
                        <div
                            v-for="group in groupedNotifications"
                            :key="group.label"
                        >
                            <!-- Rótulo do grupo -->
                            <div class="sticky top-0 z-10 border-b border-border/40 bg-popover/80 px-4 py-1.5 backdrop-blur-sm">
                                <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground/60">
                                    {{ group.label }}
                                </span>
                            </div>

                            <!-- Itens do grupo -->
                            <div
                                v-for="notification in group.items"
                                :key="notification.id"
                                :class="cn(
                                    'group/item flex cursor-pointer items-start gap-3 px-4 py-3 transition-colors',
                                    'hover:bg-accent/50',
                                    !notification.read_at
                                        ? 'bg-primary/[0.03] dark:bg-primary/[0.06]'
                                        : 'opacity-80',
                                )"
                                @click="markAsRead(notification.id)"
                            >
                                <!-- Ícone de tipo -->
                                <div
                                    :class="cn(
                                        'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg',
                                        getTypeConfig(notification.type).bg,
                                    )"
                                >
                                    <component
                                        :is="getTypeConfig(notification.type).icon"
                                        :class="cn('size-3.5', getTypeConfig(notification.type).text)"
                                    />
                                </div>

                                <!-- Conteúdo -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <p
                                            :class="cn(
                                                'truncate text-sm leading-tight',
                                                !notification.read_at
                                                    ? 'font-semibold text-foreground'
                                                    : 'font-medium text-foreground/80',
                                            )"
                                        >
                                            {{ notification.title }}
                                        </p>
                                        <!-- Indicador de não lida -->
                                        <span
                                            v-if="!notification.read_at"
                                            class="mt-1 size-1.5 shrink-0 rounded-full bg-primary"
                                            title="Não lida"
                                        />
                                    </div>

                                    <p
                                        v-if="notification.message"
                                        class="mt-0.5 line-clamp-2 text-xs text-muted-foreground"
                                    >
                                        {{ notification.message }}
                                    </p>

                                    <!-- Link de download (exportação) -->
                                    <a
                                        v-if="notification.data?.download"
                                        :href="notification.data.download"
                                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                        @click.stop
                                    >
                                        <Download class="size-3" />
                                        Baixar arquivo
                                    </a>

                                    <!-- Link para relatório de erros (importação) -->
                                    <a
                                        v-if="notification.data?.failed_report_download"
                                        :href="notification.data.failed_report_download"
                                        class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400 hover:underline"
                                        @click.stop
                                    >
                                        <FileWarning class="size-3" />
                                        Baixar erros
                                    </a>

                                    <!-- Passos de resolução -->
                                    <details
                                        v-if="notification.data?.resolution_steps?.length"
                                        class="mt-1.5"
                                    >
                                        <summary class="cursor-pointer text-xs font-medium text-muted-foreground hover:text-foreground">
                                            Como resolver
                                        </summary>
                                        <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs text-muted-foreground">
                                            <li
                                                v-for="(step, i) in notification.data.resolution_steps"
                                                :key="i"
                                                class="line-clamp-1"
                                            >
                                                {{ step }}
                                            </li>
                                        </ul>
                                    </details>

                                    <p class="mt-1 text-[10px] text-muted-foreground/60">
                                        {{ formatTime(notification.created_at) }}
                                    </p>
                                </div>

                                <!-- Botão de remover (aparece no hover) -->
                                <button
                                    type="button"
                                    title="Remover notificação"
                                    class="flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground/40 opacity-0 transition-[opacity,colors] hover:bg-destructive/10 hover:text-destructive group-hover/item:opacity-100"
                                    @click.stop="removeNotification(notification.id)"
                                >
                                    <X class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ── Rodapé ──────────────────────────────────────────────── -->
                <div
                    v-if="hasNotifications"
                    class="border-t border-border/60 px-4 py-2.5"
                >
                    <p class="text-center text-[11px] text-muted-foreground">
                        {{ notifications.length }} notificação{{ notifications.length !== 1 ? 'ões' : '' }}
                        <template v-if="hasUnread">
                            · <span class="font-medium text-primary">{{ unreadCount }} não {{ unreadCount !== 1 ? 'lidas' : 'lida' }}</span>
                        </template>
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>
