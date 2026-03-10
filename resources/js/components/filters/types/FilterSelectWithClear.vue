<!--
 * FilterSelectWithClear — filtro de seleção com botão de limpar nativo.
 *
 * Recebe a configuração de filtro no padrão SelectFilter::toArray():
 *   { name, label, placeholder, options: [{ value, label }] }
 *
 * Usa SelectWithClear nativo do pacote (sem dependências externas).
-->
<script setup lang="ts">
import SelectWithClear from '~/components/ui/select/SelectWithClear.vue'

interface FilterOption {
    value: string | number
    label: string
}

interface Filter {
    name: string
    label: string
    placeholder?: string
    options?: FilterOption[]
    searchable?: boolean
    [key: string]: any
}

interface Props {
    filter: Filter
    modelValue?: string | number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void
}>()

const handleUpdate = (value: string | null) => {
    if (!value) {
        emit('update:modelValue', null)
        return
    }
    // Preserva o tipo original da opção (number vs string)
    const original = props.filter.options?.find((opt) => String(opt.value) === value)
    emit('update:modelValue', original?.value ?? value)
}
</script>

<template>
    <SelectWithClear
        :model-value="modelValue != null ? String(modelValue) : null"
        :label="filter.label"
        :placeholder="filter.placeholder ?? filter.label"
        :options="filter.options ?? []"
        option-value="value"
        option-label="label"
        :searchable="filter.searchable ?? (filter.options?.length ?? 0) > 6"
        @update:model-value="handleUpdate"
    />
</template>
