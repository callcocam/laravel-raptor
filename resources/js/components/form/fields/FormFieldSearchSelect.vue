<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <div class="relative w-full" ref="containerRef">
      <Button 
        type="button" 
        ref="triggerRef" 
        variant="outline" 
        role="combobox"
        :disabled="column.disabled"
        :aria-expanded="open" 
        :aria-invalid="hasError"
        :class="[
          'w-full justify-between',
          hasError ? 'border-destructive' : '',
          !selectedOption && 'text-muted-foreground'
        ]" 
        @click="toggleOpen"
      >
        {{ selectedOption?.label || column.placeholder || 'Selecione...' }}
        <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>

      <Teleport to="body">
        <Transition 
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div 
            v-if="open" 
            ref="dropdownRef"
            class="absolute z-50 mt-1 rounded-md border bg-popover text-popover-foreground shadow-md p-0"
            :style="dropdownStyle"
          >
            <div class="flex flex-col w-full">
              <!-- Campo de busca -->
              <div class="border-b px-3 py-2">
                <Input 
                  ref="searchInput"
                  v-model="searchQuery"
                  type="text"
                  :placeholder="column.searchPlaceholder || 'Buscar...'"
                  class="h-9"
                  autofocus
                  :disabled="isSearching"
                  @keydown.enter.prevent="selectFirstOption"
                  @keydown.escape="open = false"
                />
              </div>

              <!-- Lista de opções -->
              <div class="max-h-[300px] overflow-y-auto p-1">
                <div v-if="isSearching" class="py-6 text-center text-sm text-muted-foreground">
                  Buscando...
                </div>

                <div v-else-if="displayOptions.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                  {{ column.emptyText || 'Nenhum resultado encontrado.' }}
                </div>

                <div v-else class="flex flex-col gap-1 w-full">
                  <button
                    v-for="(option, index) in displayOptions"
                    :key="option.value"
                    type="button"
                    :ref="el => { if (el) optionRefs[index] = el as HTMLButtonElement }"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground"
                    @click="selectOption(option.value)"
                  >
                    <span class="flex-1 text-left" v-html="highlightSearchTerm(option.label, searchQuery)"></span>
                    <CheckIcon 
                      :class="cn(
                        'ml-2 h-4 w-4',
                        internalValue === option.value ? 'opacity-100' : 'opacity-0'
                      )" 
                    />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue'
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import { useAutoComplete } from '../../../composables/useAutoComplete'
import { onClickOutside } from '@vueuse/core'
import { router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'

interface ComboboxOption {
  label: string
  value: string | number
  [key: string]: any
}

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  required?: boolean
  disabled?: boolean
  options?: ComboboxOption[] | Record<string, string>
  optionsData?: Record<string, any>
  tooltip?: string
  helpText?: string
  hint?: string
  searchable?: boolean
  searchDebounce?: number
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
  index?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
  index: 0,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const open = ref(false)
const searchQuery = ref('')
const searchInput = ref<InstanceType<typeof Input> | null>(null)
const triggerRef = ref<HTMLButtonElement | null>(null)
const containerRef = ref<HTMLElement | null>(null)
const dropdownRef = ref<HTMLDivElement | null>(null)
const optionRefs = ref<HTMLButtonElement[]>([])
const isSearching = ref(false)
const dropdownStyle = ref({ width: '0px', top: '0px', left: '0px' })

const hasError = computed(() => !!props.error)
const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

// Normaliza as options para sempre ter o formato { label, value }
const normalizedOptions = computed(() => {
  if (!props.column.options) return []

  if (Array.isArray(props.column.options)) {
    return props.column.options
  }

  // Converte objeto para array
  return Object.entries(props.column.options).map(([value, label]) => ({
    value,
    label: String(label),
  }))
})

// Se for searchable, sempre mostra as options que vêm do backend
// Se não for searchable, filtra localmente
const displayOptions = computed(() => {
  if (props.column.searchable) {
    // Backend já filtrou, só mostra o que veio
    return normalizedOptions.value
  }

  // Filtro local
  if (!searchQuery.value.trim()) {
    return normalizedOptions.value
  }

  const query = searchQuery.value.toLowerCase().trim()
  return normalizedOptions.value.filter((option) => {
    const label = option.label.toLowerCase()
    const value = String(option.value).toLowerCase()
    return label.includes(query) || value.includes(query)
  })
})

const optionsData = computed(() => {
  const data = props.column.optionsData || {}
  return Array.isArray(data) ? {} : data
})

useAutoComplete(props.column.name, props.column.autoComplete, optionsData)

const internalValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const selectedOption = computed(() => {
  if (!internalValue.value) return null
  return normalizedOptions.value.find(opt => opt.value === internalValue.value)
})

function highlightSearchTerm(text: string, searchTerm: string) {
  if (!searchTerm?.trim()) return text

  const term = searchTerm.trim()
  const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  
  return text.replace(regex, '<mark class="bg-yellow-400/50 text-yellow-900 dark:text-yellow-100 font-medium">$1</mark>')
}

function toggleOpen() {
  open.value = !open.value
}

function selectOption(selectedValue: string | number) {
  internalValue.value = selectedValue === internalValue.value ? null : selectedValue
  open.value = false
  searchQuery.value = ''
}

function selectFirstOption() {
  if (displayOptions.value.length > 0) {
    selectOption(displayOptions.value[0].value)
  }
}

onClickOutside(dropdownRef, (event) => {
  if (triggerRef.value) {
    const triggerEl = (triggerRef.value as any).$el as HTMLElement || triggerRef.value
    if (triggerEl?.contains?.(event.target as Node)) return
  }
  open.value = false
}, { ignore: [containerRef] })

// Busca no backend
function performSearch(query: string) {
  if (!props.column.searchable) return

  isSearching.value = true

  router.get(window.location.pathname, { [props.column.name]: query }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['form'],
    onFinish: () => {
      isSearching.value = false
      nextTick(() => {
        searchInput.value?.$el?.focus()
      })
    }
  })
}

const debouncedSearch = useDebounceFn(
  (query: string) => performSearch(query),
  props.column.searchDebounce || 300
)

watch(searchQuery, (newQuery) => {
  if (props.column.searchable) {
    debouncedSearch(newQuery)
  }
})

function updateDropdownPosition() {
  if (!triggerRef.value || !dropdownRef.value) return

  const triggerEl = (triggerRef.value as any).$el as HTMLElement || triggerRef.value
  if (!triggerEl?.getBoundingClientRect) return

  const triggerRect = triggerEl.getBoundingClientRect()

  dropdownStyle.value = {
    width: `${triggerRect.width}px`,
    top: `${triggerRect.bottom + window.scrollY + 4}px`,
    left: `${triggerRect.left + window.scrollX}px`,
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    nextTick(() => {
      updateDropdownPosition()
    })

    const handleUpdate = () => updateDropdownPosition()
    window.addEventListener('resize', handleUpdate)
    window.addEventListener('scroll', handleUpdate, true)

    const unwatch = watch(() => open.value, (newValue) => {
      if (!newValue) {
        window.removeEventListener('resize', handleUpdate)
        window.removeEventListener('scroll', handleUpdate, true)
        unwatch()
      }
    })
  } else {
    searchQuery.value = ''
  }
})
</script>