<script setup lang="ts">
import type { Ref } from 'vue'
import { inject } from 'vue'

const open = inject<Ref<boolean>>('popoverOpen')!

defineProps<{ asChild?: boolean }>()
</script>

<template>
    <slot v-if="$props.asChild" />
    <button v-else type="button" @click="open.value = !open.value">
        <slot />
    </button>
</template>
