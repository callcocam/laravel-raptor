<script setup lang="ts">
import { provide } from 'vue'

const open = defineModel<boolean>('open', { default: false })
provide('collapsibleOpen', open)
</script>

<template>
    <div><slot /></div>
</template>
