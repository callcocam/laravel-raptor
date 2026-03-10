<script setup lang="ts">
import type { Ref } from 'vue'
import { computed, inject } from 'vue'

const props = defineProps<{ placeholder?: string }>()

const modelValue  = inject<Ref<string | number | null | undefined>>('selectModelValue')!
const getLabel    = inject<(v: string | number | null | undefined) => string>('selectGetLabel')!

const displayText = computed(() =>
    modelValue.value != null && modelValue.value !== ''
        ? (getLabel(modelValue.value) || String(modelValue.value))
        : (props.placeholder ?? 'Selecione...')
)

const hasValue = computed(() => modelValue.value != null && modelValue.value !== '')
</script>

<template>
    <span :class="hasValue ? 'text-foreground' : 'text-muted-foreground'">
        {{ displayText }}
    </span>
</template>
