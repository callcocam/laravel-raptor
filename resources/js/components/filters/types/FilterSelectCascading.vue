<!--
 * FilterSelectCascading — filtro em cascata (vários níveis).
 * - Recebe modelValue (objeto com valor por field.name) e emite update:modelValue({ fieldName, value }).
 * - Se filter.includeParentsParam existir, mostra checkbox "Incluir categorias pai": quando ativo,
 *   o backend aplica whereIn(column, [todos os níveis selecionados]) em vez de where(column, último).
-->
<template>
    <div class="space-y-1">
        <FilterSelectCascadingItem
            v-for="field in fields"
            :key="field.name"
            :filter="field"
            :model-value="getFieldValue(field.name)"
            @update:model-value="(value) => emitFieldChange(field.name, value)"
        />
        <!-- Opção "Incluir pais": filtra pelo último nível OU por qualquer categoria pai no caminho -->
        <label
            v-if="includeParentsParam"
            class="flex items-center gap-2 mt-2 text-sm text-muted-foreground cursor-pointer"
        >
            <Checkbox
                :checked="includeParentsValue"
                @update:model-value="(v) => onIncludeParentsChange(v === true)"
            />
            <span>Incluir categorias pai</span>
        </label>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Checkbox } from '~/components/ui/checkbox'
import FilterSelectCascadingItem from './FilterSelectCascadingItem.vue'

interface FilterOption {
    value: string | number
    label: string
}

interface Field {
    name: string
    label: string
    placeholder?: string
    options?: FilterOption[]
    searchable?: boolean
    dependsOn?: string
}

interface Filter {
    name: string
    label: string
    placeholder?: string
  fields?: Field[]
  fieldsUsing?: string
  /** Se presente, mostra opção "Incluir categorias pai" (param na URL, ex: category_id_include_parents) */
  includeParentsParam?: string
  searchable?: boolean
  [key: string]: any
}

interface Props {
    filter: Filter
    /** Objeto { [field.name]: value } preenchido pelo TableFilters (filterValues por campo). */
    modelValue?: Record<string, string | number | null> | string | number | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: { fieldName: string; value: string | number | boolean | null }): void
}>()

const fields = computed(() => props.filter.fields ?? [])
const includeParentsParam = computed(() => props.filter.includeParentsParam ?? null)
const includeParentsValue = computed(() => {
    if (!includeParentsParam.value) return false
    const mv = props.modelValue
    if (mv === null || mv === undefined || typeof mv !== 'object' || Array.isArray(mv)) return false
    const v = mv[includeParentsParam.value]
    return v === true || v === '1' || v === 'true'
})

function getFieldValue(fieldName: string): string | number | null {
    const mv = props.modelValue
    if (mv === null || mv === undefined) return null
    if (typeof mv === 'object' && !Array.isArray(mv)) {
        const v = mv[fieldName]
        return v !== undefined && v !== '' ? v : null
    }
    return null
}

function emitFieldChange(fieldName: string, value: string | number | null) {
    emit('update:modelValue', { fieldName, value })
}

function onIncludeParentsChange(checked: boolean) {
    const param = includeParentsParam.value
    if (param) emit('update:modelValue', { fieldName: param, value: checked })
}
</script>