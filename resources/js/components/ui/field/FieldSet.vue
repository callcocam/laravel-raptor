<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
    <fieldset :class="cn('border border-border rounded-lg p-4', props.class)">
        <slot />
    </fieldset>
</template>
