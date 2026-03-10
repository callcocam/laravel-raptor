<script setup lang="ts">
import type { Ref } from 'vue'
import { inject } from 'vue'

const open = inject<Ref<boolean>>('collapsibleOpen')!

const asChild = defineProps<{ asChild?: boolean }>()
</script>

<template>
    <slot v-if="asChild" :toggle="() => (open.value = !open.value)" />
    <button v-else type="button" @click="open.value = !open.value">
        <slot />
    </button>
</template>
