<script setup lang="ts">
import { computed } from 'vue'
import { useGlobalNotifications } from '../composables/useGlobalNotifications'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Bell, CheckCheck, Trash2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const {
    notifications,
    unreadCount,
    markAsRead,
    markAllAsRead,
    removeNotification,
    clearAll,
} = useGlobalNotifications()

const hasNotifications = computed(() => notifications.value.length > 0)
const hasUnread = computed(() => unreadCount.value > 0)

const formatTime = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diff = now.getTime() - date.getTime()
    const minutes = Math.floor(diff / 60000)
    const hours = Math.floor(diff / 3600000)
    const days = Math.floor(diff / 86400000)

    if (minutes < 1) return 'Agora'
    if (minutes < 60) return `${minutes}m atrás`
    if (hours < 24) return `${hours}h atrás`
    if (days < 7) return `${days}d atrás`
    return date.toLocaleDateString('pt-BR')
}

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'success':
            return '✅'
        case 'error':
            return '❌'
        case 'warning':
            return '⚠️'
        default:
            return 'ℹ️'
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative"
            >
                <Bell class="h-5 w-5" />
                <Badge
                    v-if="hasUnread"
                    variant="destructive"
                    class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 text-xs"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80">
            <div class="flex items-center justify-between p-4">
                <h3 class="font-semibold text-sm">Notificações</h3>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="hasUnread"
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs"
                        @click="markAllAsRead"
                    >
                        <CheckCheck class="h-3 w-3 mr-1" />
                        Marcar todas como lidas
                    </Button>
                    <Button
                        v-if="hasNotifications"
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs text-destructive"
                        @click="clearAll"
                    >
                        <Trash2 class="h-3 w-3 mr-1" />
                        Limpar
                    </Button>
                </div>
            </div>

            <DropdownMenuSeparator />

            <ScrollArea class="h-[400px]">
                <div v-if="!hasNotifications" class="p-8 text-center text-muted-foreground">
                    <Bell class="h-8 w-8 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Nenhuma notificação</p>
                </div>

                <div v-else class="divide-y">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        :class="cn(
                            'p-4 hover:bg-accent transition-colors cursor-pointer',
                            !notification.read_at && 'bg-accent/50'
                        )"
                        @click="markAsRead(notification.id)"
                    >
                        <div class="flex items-start gap-3">
                            <span class="text-lg mt-0.5">
                                {{ getTypeIcon(notification.type) }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p
                                        :class="cn(
                                            'text-sm font-medium',
                                            !notification.read_at && 'font-semibold'
                                        )"
                                    >
                                        {{ notification.title }}
                                    </p>
                                    <Badge
                                        v-if="!notification.read_at"
                                        variant="secondary"
                                        class="h-4 w-4 p-0 flex-shrink-0"
                                    />
                                </div>
                                <p
                                    v-if="notification.message"
                                    class="text-xs text-muted-foreground mt-1 line-clamp-2"
                                >
                                    {{ notification.message }}
                                </p>
                                <!-- Link de download para exportações -->
                                <a
                                    v-if="notification.data?.download"
                                    :href="notification.data.download"
                                    class="inline-flex items-center gap-1 mt-2 text-xs font-medium text-primary hover:underline"
                                    @click.stop
                                >
                                    📥 Baixar arquivo
                                </a>
                                <!-- Passos de resolução para erros de banco de dados -->
                                <div
                                    v-if="notification.data?.resolution_steps && notification.data.resolution_steps.length > 0"
                                    class="mt-2 space-y-1"
                                >
                                    <p class="text-xs font-medium text-muted-foreground">Como resolver:</p>
                                    <ul class="text-xs text-muted-foreground space-y-1 list-disc list-inside">
                                        <li
                                            v-for="(step, index) in notification.data.resolution_steps"
                                            :key="index"
                                            class="line-clamp-1"
                                        >
                                            {{ step }}
                                        </li>
                                    </ul>
                                </div>
                                <p class="text-xs text-muted-foreground mt-2">
                                    {{ formatTime(notification.created_at) }}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-6 w-6 flex-shrink-0"
                                @click.stop="removeNotification(notification.id)"
                            >
                                <Trash2 class="h-3 w-3" />
                            </Button>
                        </div>
                    </div>
                </div>
            </ScrollArea>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

