<script setup lang="ts">
/**
 * DialogContent — painel do modal, teleportado para <body>.
 * Usa provide/inject para ler o estado do Dialog pai.
 */
import type { HTMLAttributes, Ref } from 'vue'
import { inject } from 'vue'
import { X } from 'lucide-vue-next'
import { cn } from '~/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
}>()

const open   = inject<Ref<boolean>>('dialogOpen')!
const close  = inject<() => void>('dialogClose')!
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                    @click="close"
                />

                <!-- Painel -->
                <Transition
                    enter-active-class="duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="open"
                        :class="cn(
                            'relative z-10 w-full max-w-lg rounded-xl border border-border',
                            'bg-background text-foreground shadow-xl',
                            'flex flex-col gap-4 p-6',
                            props.class,
                        )"
                        @click.stop
                    >
                        <!-- Botão fechar -->
                        <button
                            type="button"
                            class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            @click="close"
                        >
                            <X class="size-4" />
                            <span class="sr-only">Fechar</span>
                        </button>

                        <slot />
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
