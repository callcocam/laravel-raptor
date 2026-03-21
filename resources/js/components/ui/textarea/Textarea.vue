<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'

const props = withDefaults(defineProps<{
    id?: string
    name?: string
    placeholder?: string
    required?: boolean
    disabled?: boolean
    readonly?: boolean
    rows?: number
    maxlength?: number
    modelValue?: string | null
    class?: HTMLAttributes['class']
    ariaInvalid?: boolean
}>(), {
    rows: 3,
    modelValue: null,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()
</script>

<template>
    <textarea
        :id="props.id"
        :name="props.name"
        :placeholder="props.placeholder"
        :required="props.required"
        :disabled="props.disabled"
        :readonly="props.readonly"
        :rows="props.rows"
        :maxlength="props.maxlength"
        :aria-invalid="props.ariaInvalid"
        :value="props.modelValue ?? ''"
        :class="cn(
            'flex min-h-[80px] w-full rounded-lg bg-[var(--color-input-surface)] border-[1.5px] border-transparent px-3 py-2',
            'text-sm text-foreground placeholder:text-muted-foreground',
            'transition-[border-color,box-shadow,background-color] outline-none',
            'focus:bg-card focus:border-[var(--color-input-focus)] focus:ring-2 focus:ring-[var(--color-input-focus)]/15',
            'disabled:cursor-not-allowed disabled:opacity-50 resize-y',
            'aria-invalid:border-destructive aria-invalid:ring-destructive/20',
            props.class,
        )"
        @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
</template>
