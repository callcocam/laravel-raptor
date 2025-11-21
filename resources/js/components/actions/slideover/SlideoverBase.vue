<!--
 * SlideoverBase - Estrutura base do slideover
 *
 * Componente base que fornece:
 * - Overlay (backdrop)
 * - Painel lateral animado (left/right)
 * - Header com título e botão fechar
 * - Slot para conteúdo dinâmico
 -->
<template>
  <div>
    <!-- Overlay (backdrop) -->
    <Transition
      enter-active-class="transition-opacity duration-300"
      leave-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-40"
        @click="emit('close')"
      />
    </Transition>

    <!-- Painel Slideover -->
    <Transition
      :enter-active-class="`transition-transform duration-300 ease-out`"
      :leave-active-class="`transition-transform duration-300 ease-in`"
      :enter-from-class="slideoverEnterClass"
      :leave-to-class="slideoverLeaveClass"
    >
      <div
        v-if="isOpen"
        :class="[
          'fixed top-0 bottom-0 z-50 bg-background shadow-2xl',
          'w-full sm:max-w-md lg:max-w-lg',
          'flex flex-col',
          slideoverPositionClass,
        ]"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b px-6 py-4">
          <div class="flex-1">
            <h2 class="text-lg font-semibold">
              {{ title }}
            </h2>
            <p v-if="description" class="text-sm text-muted-foreground mt-1">
              {{ description }}
            </p>
          </div>
          <Button variant="ghost" size="icon" @click="emit('close')" class="ml-4">
            <component :is="closeIcon" class="h-4 w-4" />
          </Button>
        </div>

        <!-- Slot para conteúdo dinâmico -->
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { Button } from '@/components/ui/button'
import { X } from 'lucide-vue-next'

interface Props {
  isOpen: boolean
  title: string
  description?: string
  position?: 'left' | 'right'
}

const props = withDefaults(defineProps<Props>(), {
  position: 'right'
})

const emit = defineEmits<{
  (e: 'close'): void
}>()

const closeIcon = h(X)

const slideoverPositionClass = computed(() => {
  return props.position === 'left' ? 'left-0' : 'right-0'
})

const slideoverEnterClass = computed(() => {
  return props.position === 'left' ? '-translate-x-full' : 'translate-x-full'
})

const slideoverLeaveClass = computed(() => {
  return props.position === 'left' ? '-translate-x-full' : 'translate-x-full'
})
</script>
