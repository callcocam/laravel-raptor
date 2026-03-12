<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed } from 'vue'
import { cn } from '~/lib/utils'
import { Check, Minus } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
    id?: string
    name?: string
    /** @deprecated Prefer modelValue for v-model */
    checked?: boolean | 'indeterminate'
    /** Bound value (v-model:model-value or :model-value) */
    modelValue?: boolean | 'indeterminate'
    /** When true, show indeterminate state regardless of modelValue/checked */
    indeterminate?: boolean
    required?: boolean
    disabled?: boolean
    class?: HTMLAttributes['class']
    ariaInvalid?: boolean
}>(), {
    checked: false,
    modelValue: undefined,
    indeterminate: false,
})

const emit = defineEmits<{
    (e: 'update:checked', value: boolean): void
    (e: 'update:modelValue', value: boolean | 'indeterminate'): void
}>()

/** Effective value: modelValue takes precedence for v-model compatibility */
const effectiveValue = computed<boolean | 'indeterminate'>(() => {
    const v = props.modelValue ?? props.checked
    return v ?? false
})

/** Display as indeterminate when prop is set or when value is 'indeterminate' */
const isIndeterminate = computed(() => props.indeterminate || effectiveValue.value === 'indeterminate')

/** Normalizado para exibição: qualquer valor truthy (exceto 'indeterminate') = marcado */
const isCheckedDisplay = computed((): boolean | 'indeterminate' => {
    if (isIndeterminate.value) return 'indeterminate'
    const v = effectiveValue.value
    if (v === 'indeterminate') return 'indeterminate'
    return v === true || v === 'true' || v === 1
})

const toggle = () => {
    if (props.disabled) return
    const next = isIndeterminate.value ? true : isCheckedDisplay.value ? false : true
    emit('update:checked', next === true)
    emit('update:modelValue', next)
}
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :id="props.id"
        :name="props.name"
        :aria-checked="isIndeterminate ? 'mixed' : !!isCheckedDisplay"
        :aria-invalid="props.ariaInvalid"
        :aria-required="props.required"
        :disabled="props.disabled"
        :class="cn(
            'peer inline-flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded-sm border border-primary',
            'ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2',
            'focus-visible:ring-ring focus-visible:ring-offset-2',
            'disabled:cursor-not-allowed disabled:opacity-50',
            (isCheckedDisplay === true || isIndeterminate)
                ? 'bg-primary text-primary-foreground'
                : 'bg-background',
            props.class,
        )"
        @click="toggle"
        @keydown.space.prevent="toggle"
    >
        <Minus v-if="isIndeterminate" class="size-3 stroke-[3]" />
        <Check v-else-if="isCheckedDisplay === true" class="size-3 stroke-[3] shrink-0" />
    </button>
</template>
