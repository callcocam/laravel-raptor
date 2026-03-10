<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '~/lib/utils'
import { Check, Minus } from 'lucide-vue-next'

const props = withDefaults(defineProps<{
    id?: string
    name?: string
    checked?: boolean | 'indeterminate'
    required?: boolean
    disabled?: boolean
    class?: HTMLAttributes['class']
    ariaInvalid?: boolean
}>(), {
    checked: false,
})

const emit = defineEmits<{
    (e: 'update:checked', value: boolean): void
    (e: 'update:modelValue', value: boolean | 'indeterminate'): void
}>()

const toggle = () => {
    if (props.disabled) return
    const next = props.checked === 'indeterminate' ? true : !props.checked
    emit('update:checked', next)
    emit('update:modelValue', next)
}
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :id="props.id"
        :name="props.name"
        :aria-checked="props.checked === 'indeterminate' ? 'mixed' : props.checked"
        :aria-invalid="props.ariaInvalid"
        :aria-required="props.required"
        :disabled="props.disabled"
        :class="cn(
            'peer inline-flex h-4 w-4 shrink-0 cursor-pointer items-center justify-center rounded-sm border border-primary',
            'ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2',
            'focus-visible:ring-ring focus-visible:ring-offset-2',
            'disabled:cursor-not-allowed disabled:opacity-50',
            (props.checked === true || props.checked === 'indeterminate')
                ? 'bg-primary text-primary-foreground'
                : 'bg-background',
            props.class,
        )"
        @click="toggle"
        @keydown.space.prevent="toggle"
    >
        <Minus v-if="props.checked === 'indeterminate'" class="size-3 stroke-[3]" />
        <Check v-else-if="props.checked" class="size-3 stroke-[3]" />
    </button>
</template>
