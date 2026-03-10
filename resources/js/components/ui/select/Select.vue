<script setup lang="ts">
import { provide, ref } from 'vue'
import { useEventListener } from '@vueuse/core'

const props = withDefaults(defineProps<{
    required?: boolean
    disabled?: boolean
}>(), {})

const modelValue = defineModel<string | number | null | undefined>()

const open = ref(false)
const registeredItems = ref<Array<{ value: string; label: string }>>([])

const toggle = () => { if (!props.disabled) open.value = !open.value }
const close  = () => { open.value = false }

const selectValue = (value: string) => {
    modelValue.value = value
    close()
}

const registerItem = (item: { value: string; label: string }) => {
    if (!registeredItems.value.find(i => i.value === item.value)) {
        registeredItems.value.push(item)
    }
}

const getLabelForValue = (value: string | number | null | undefined): string => {
    if (value == null) return ''
    const item = registeredItems.value.find(i => i.value === String(value))
    return item?.label ?? String(value)
}

provide('selectOpen', open)
provide('selectDisabled', props.disabled)
provide('selectModelValue', modelValue)
provide('selectToggle', toggle)
provide('selectClose', close)
provide('selectValue', selectValue)
provide('selectRegisterItem', registerItem)
provide('selectGetLabel', getLabelForValue)

useEventListener('keydown', (e: KeyboardEvent) => {
    if (e.key === 'Escape' && open.value) close()
})

useEventListener('pointerdown', (e: PointerEvent) => {
    if (open.value && !(e.target as Element)?.closest?.('[data-select-root]')) {
        close()
    }
})
</script>

<template>
    <div class="relative" data-select-root>
        <slot />
    </div>
</template>
