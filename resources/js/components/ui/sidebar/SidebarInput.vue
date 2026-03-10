<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Input } from '../input'

const props = defineProps<{
    class?: HTMLAttributes['class']
    modelValue?: string | number
    placeholder?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string | number]
}>()
</script>

<template>
    <Input
        data-sidebar="input"
        data-slot="sidebar-input"
        :model-value="modelValue"
        :placeholder="placeholder"
        :class="cn(
            'bg-background h-8 w-full shadow-none',
            'focus-visible:ring-sidebar-ring',
            props.class,
        )"
        @update:model-value="emit('update:modelValue', $event)"
    />
</template>
