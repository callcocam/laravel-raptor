<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { provide, ref } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()

const search = ref('')
provide('commandSearch', search)
</script>

<template>
    <div :class="cn('flex h-full w-full flex-col overflow-hidden rounded-md bg-popover text-popover-foreground', props.class)">
        <slot />
    </div>
</template>
