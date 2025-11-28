<!--
 * FormFieldRelationship - Select with dynamic search for relationships
 *
 * Supports:
 * - Dynamic search with debounce
 * - Multiple selection
 * - Preload options
 * - AutoComplete integration
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          role="combobox"
          :aria-expanded="open"
          :class="[
            'w-full justify-between h-9',
            hasError ? 'border-destructive' : '',
            !selectedOption && 'text-muted-foreground'
          ]"
        >
          <span class="truncate">
            {{ selectedOption ? selectedOption.label : (column.placeholder || 'Selecione...') }}
          </span>
          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
        <Command v-model="searchQuery" :filter-function="() => 1">
          <CommandInput 
            v-model="searchQuery"
            :placeholder="`Buscar ${column.label?.toLowerCase() || 'item'}...`" 
          />
          <CommandEmpty>
            {{ isSearching ? 'Buscando...' : 'Nenhum resultado encontrado.' }}
          </CommandEmpty>
          <CommandList>
            <CommandGroup>
              <CommandItem
                v-for="option in filteredOptions"
                :key="option.value"
                :value="option.value"
                @select="handleSelect(option)"
              >
                <Check
                  :class="[
                    'mr-2 h-4 w-4',
                    internalValue === option.value ? 'opacity-100' : 'opacity-0'
                  ]"
                />
                {{ option.label }}
              </CommandItem>
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { Button } from '@/components/ui/button'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import { Check, ChevronsUpDown } from 'lucide-vue-next'
import { useAutoComplete } from '../../../composables/useAutoComplete'
import { useDebounceFn } from '@vueuse/core'

interface RelationshipOption {
  label: string
  value: string | number
  data?: Record<string, any>
}

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  relationship?: string
  searchable?: boolean
  multiple?: boolean
  preload?: boolean
  searchMinLength?: number
  searchDebounce?: number
  titleAttribute?: string
  keyAttribute?: string
  options?: RelationshipOption[] | Record<string, string>
  optionsData?: Record<string, any>
  tooltip?: string
  helpText?: string
  hint?: string
  autoComplete?: {
    enabled: boolean
    fields: Array<{ source: string, target: string }>
    optionValueKey: string | null
    optionLabelKey: string | null
    returnFullObject: boolean
  }
}

interface Props {
  column: FormColumn
  modelValue?: string | number | null
  error?: string | string[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const page = usePage()
const open = ref(false)
const searchQuery = ref('')
const isSearching = ref(false)
const searchResults = ref<RelationshipOption[]>([])

const hasError = computed(() => !!props.error)

const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

// Normaliza options iniciais
const initialOptions = computed(() => {
  if (!props.column.options) return []

  if (!Array.isArray(props.column.options)) {
    return Object.entries(props.column.options).map(([value, label]) => ({
      value,
      label: String(label),
    }))
  }

  return props.column.options
})

// Opções filtradas (iniciais + resultados da busca)
const filteredOptions = computed(() => {
  if (searchQuery.value && searchResults.value.length > 0) {
    return searchResults.value
  }
  return initialOptions.value
})

// Opção selecionada
const selectedOption = computed(() => {
  return filteredOptions.value.find(opt => String(opt.value) === String(internalValue.value))
})

// Computed para optionsData
const optionsData = computed(() => {
  const data = props.column.optionsData || {}
  return Array.isArray(data) ? {} : data
})

// Configura autoComplete se habilitado
useAutoComplete(props.column.name, props.column.autoComplete, optionsData)

// Busca dinâmica com debounce
const performSearch = useDebounceFn((query: string) => {
  if (!props.column.searchable || !props.column.relationship) {
    return
  }

  const minLength = props.column.searchMinLength || 2
  if (query.length < minLength) {
    searchResults.value = []
    return
  }

  isSearching.value = true

  // Monta a URL de busca
  const currentUrl = new URL(window.location.href)
  const searchParams = new URLSearchParams(currentUrl.search)
  
  // Adiciona parâmetros de busca
  searchParams.set(`search_${props.column.name}`, query)
  searchParams.set(`relationship`, props.column.relationship)
  
  // Faz a requisição via Inertia mantendo o estado da página
  router.reload({
    data: Object.fromEntries(searchParams),
    only: [props.column.name + '_search_results'],
    onSuccess: (pageResponse) => {
      // Processa os resultados
      const results = (pageResponse.props as any)[props.column.name + '_search_results']
      if (results && Array.isArray(results)) {
        searchResults.value = results
      }
      isSearching.value = false
    },
    onError: () => {
      isSearching.value = false
    }
  })
}, props.column.searchDebounce || 300)

// Watch na query de busca
watch(searchQuery, (newQuery) => {
  if (newQuery && props.column.searchable) {
    performSearch(newQuery)
  } else {
    searchResults.value = []
  }
})

const handleSelect = (option: RelationshipOption) => {
  internalValue.value = String(option.value)
  open.value = false
  searchQuery.value = ''
}

const internalValue = computed({
  get: () => props.modelValue ? String(props.modelValue) : undefined,
  set: (value) => {
    emit('update:modelValue', value || null)
  },
})
</script>
