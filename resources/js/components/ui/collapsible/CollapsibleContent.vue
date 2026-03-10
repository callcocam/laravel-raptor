<script setup lang="ts">
import type { Ref } from 'vue'
import { inject } from 'vue'

const open = inject<Ref<boolean>>('collapsibleOpen')!
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-200 ease-out overflow-hidden"
        leave-active-class="transition-all duration-150 ease-in overflow-hidden"
        enter-from-class="max-h-0 opacity-0"
        enter-to-class="max-h-[2000px] opacity-100"
        leave-from-class="max-h-[2000px] opacity-100"
        leave-to-class="max-h-0 opacity-0"
    >
        <div v-if="open"><slot /></div>
    </Transition>
</template>
