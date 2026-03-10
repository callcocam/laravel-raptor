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
            'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2',
            'text-sm ring-offset-background placeholder:text-muted-foreground',
            'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
            'disabled:cursor-not-allowed disabled:opacity-50 resize-y',
            props.class,
        )"
        @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
</template>
