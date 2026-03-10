<script setup lang="ts">
import { provide, ref } from 'vue'

const open = ref(false)
provide('popoverOpen', open)
provide('popoverClose', () => { open.value = false })
</script>

<template>
    <div class="relative inline-block">
        <slot />
    </div>
</template>
