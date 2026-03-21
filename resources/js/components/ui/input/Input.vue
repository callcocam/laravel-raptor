<script setup lang="ts">
import type { HTMLAttributes, InputHTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = defineProps<{
    class?: HTMLAttributes['class']
    type?: InputHTMLAttributes['type']
    modelValue?: string | number
    defaultValue?: string | number
    placeholder?: string
    disabled?: boolean
    readonly?: boolean
    required?: boolean
    id?: string
    name?: string
    autocomplete?: string
    min?: string | number
    max?: string | number
    step?: string | number
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string | number]
}>()

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement
    emit('update:modelValue', target.value)
}
</script>

<template>
    <input
        data-slot="input"
        :type="type ?? 'text'"
        :value="modelValue ?? defaultValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :id="id"
        :name="name"
        :autocomplete="autocomplete"
        :min="min"
        :max="max"
        :step="step"
        :class="cn(
            'flex h-9 w-full min-w-0 rounded-lg bg-[var(--color-input-surface)] border-[1.5px] border-transparent px-3 py-1 text-sm',
            'text-foreground placeholder:text-muted-foreground',
            'transition-[color,border-color,box-shadow,background-color] outline-none',
            'focus:bg-card focus:border-[var(--color-input-focus)] focus:ring-2 focus:ring-[var(--color-input-focus)]/15',
            'dark:focus:ring-[var(--color-input-focus)]/20',
            'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
            'file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground',
            'selection:bg-primary selection:text-primary-foreground',
            'aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
            props.class,
        )"
        @input="handleInput"
    >
</template>
