<!--
 * FormFieldCombobox - Combobox/autocomplete field usando shadcn-vue Field primitives
 *
 * Searchable select com funcionalidade de autocomplete
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <!-- Container relativo para posicionar o dropdown -->
    <div class="relative w-full" ref="containerRef">
      <Button type="button" ref="triggerRef" variant="outline" role="combobox" :disabled="column.disabled"
        :aria-expanded="open" :aria-invalid="hasError" :class="[
          'w-full justify-between',
          hasError ? 'border-destructive' : '',
          !selectedOption && 'text-muted-foreground'
        ]" @click="toggleOpen">
        {{ selectedOption?.label || column.placeholder || 'Selecione...' }}
        <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>

      <!-- Dropdown personalizado -->
      <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
          <div v-if="open" ref="dropdownRef"
            class="absolute z-50 mt-1 rounded-md border bg-popover text-popover-foreground shadow-md p-0"
            :style="dropdownStyle">
            <div class="flex flex-col w-full">
              <!-- Campo de busca -->
              <div class="border-b px-3 py-2" ref="searchInputContainer">
                <Input ref="searchInput" v-model="searchQuery" type="text" :id="columnId + '_search'"
                  :placeholder="column.searchPlaceholder || 'Buscar...'" class="h-9" autofocus="true"
                  :disabled="isSearching" @keydown.enter.prevent="selectFirstFiltered" @keydown.escape="open = false" />
              </div>

              <!-- Lista de opções -->
              <div class="max-h-[300px] overflow-y-auto p-1">
                <!-- Loading -->
                <div v-if="isSearching" class="py-6 text-center text-sm text-muted-foreground">
                  Buscando...
                </div>

                <!-- Empty state -->
                <div v-else-if="filteredOptions.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                  {{ column.emptyText || 'Nenhum resultado encontrado.' }}
                </div>

                <!-- Options list -->
                <div v-else class="flex flex-col gap-1 w-full " ref="optionsListRef" :id="columnId">
                  <button v-for="(option, index) in filteredOptions" :key="getOptionValue(option)" type="button"
                    :ref="el => { if (el) optionRefs[index] = el as HTMLButtonElement }"
                    class="relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                    @click="selectOption(getOptionValue(option))"
                    @keydown.enter.prevent="selectOption(getOptionValue(option))"
                    @keydown.space.prevent="selectOption(getOptionValue(option))">
                    <span class="flex-1 text-left"
                      v-html="highlightSearchTerm(getOptionLabel(option), searchQuery)"></span>
                    <CheckIcon :class="cn(
                      'ml-2 h-4 w-4',
                      internalValue === getOptionValue(option)
                        ? 'opacity-100'
                        : 'opacity-0'
                    )
                      " />
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
import { computed, ref, watch, nextTick, onMounted } from 'vue'
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
  label?: string
  value?: string | number
  data?: Record<string, any>
  [key: string]: any
}

interface FormColumn {
  name: string
  index?: number
  label?: string
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  options?: ComboboxOption[] | Record<string, string>
  optionsData?: Record<string, any>
  tooltip?: string
  helpText?: string
  hint?: string
  searchable?: boolean
  searchUrl?: string // URL customizada para busca
  searchDebounce?: number // Tempo de debounce em ms (default: 300)
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
const columnId = computed(() => props.modelValue ? props.modelValue.toString() : 'form')

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | null): void
}>()

const open = ref(false)
const searchQuery = ref('')
const searchInput = ref<InstanceType<typeof Input> | null>(null)
const searchInputContainer = ref<HTMLDivElement | null>(null)
const triggerRef = ref<HTMLButtonElement | null>(null)
const containerRef = ref<HTMLElement | null>(null)
const dropdownRef = ref<HTMLDivElement | null>(null)
const optionRefs = ref<HTMLButtonElement[]>([])
const isSearching = ref(false)
const searchResults = ref<ComboboxOption[]>([])
const dropdownStyle = ref<{ width: string; top: string; left: string }>({
  width: '0px',
  top: '0px',
  left: '0px',
})
const optionsListRef = ref<HTMLDivElement | undefined>(undefined)

const hasError = computed(() => !!props.error)
const errorArray = computed(() => {
  if (!props.error) return []
  if (Array.isArray(props.error)) {
    return props.error.map(msg => ({ message: msg }))
  }
  return [{ message: props.error }]
})

const options = computed(() => {
  if (!props.column.options) return []

  if (!Array.isArray(props.column.options)) {
    return Object.entries(props.column.options).map(([value, label]) => ({
      value,
      label,
    }))
  }

  return props.column.options
})

// Watch para atualizar os resultados quando as opções mudarem (após busca no backend)
watch(() => props.column.options, (newOptions) => {
  if (props.column.searchable && open.value) {
    // Se as opções foram atualizadas e há uma busca ativa, atualiza os resultados
    if (searchQuery.value.trim() && newOptions) {
      if (Array.isArray(newOptions)) {
        searchResults.value = newOptions
      } else if (typeof newOptions === 'object') {
        // Converte objeto para array
        searchResults.value = Object.entries(newOptions).map(([value, label]) => ({
          value,
          label,
        }))
      }
    }
  }
}, { deep: true })

const optionsData = computed(() => {
  const data = props.column.optionsData || {}
  return Array.isArray(data) ? {} : data
})

// Configura autoComplete se habilitado
useAutoComplete(props.column.name, props.column.autoComplete, optionsData)

const internalValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const selectedOption = computed(() => {
  if (!internalValue.value) return null

  // Se for searchable, busca primeiro nos resultados da busca, depois nas opções padrão
  if (props.column.searchable) {
    // Primeiro tenta encontrar nos resultados da busca
    const foundInSearch = searchResults.value.find(
      (option) => getOptionValue(option) === internalValue.value
    )
    if (foundInSearch) return foundInSearch

    // Se não encontrou, busca nas opções padrão
    return options.value.find(
      (option) => getOptionValue(option) === internalValue.value
    )
  }

  // Se não for searchable, busca apenas nas opções padrão
  return options.value.find(
    (option) => getOptionValue(option) === internalValue.value
  )
})

// Filtra opções baseado na busca
const filteredOptions = computed(() => {
  // Se for searchable, usa os resultados da busca
  if (props.column.searchable) {
    // Se a busca estiver vazia, mostra as opções iniciais
    if (!searchQuery.value.trim()) {
      return options.value
    }
    // Se houver busca, mostra os resultados da busca
    return searchResults.value
  }

  // Busca local (quando não é searchable)
  if (!searchQuery.value.trim()) {
    return options.value
  }

  const query = searchQuery.value.toLowerCase().trim()

  return options.value.filter((option) => {
    const label = getOptionLabel(option).toLowerCase()
    const value = String(getOptionValue(option)).toLowerCase()

    return label.includes(query) || value.includes(query)
  })
})

function getOptionValue(option: ComboboxOption): string {
  if (typeof option === 'object' && option !== null) {
    return String(option.value ?? option.label ?? '')
  }
  return String(option)
}

function getOptionLabel(option: ComboboxOption): string {
  if (typeof option === 'object' && option !== null) {
    return String(option.label ?? option.value ?? '')
  }
  return String(option)
}

// Função para destacar o termo pesquisado no texto
function highlightSearchTerm(text: string, searchTerm: string) {
  if (!searchTerm || !searchTerm.trim()) {
    return text
  }

  const term = searchTerm.trim()
  const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
  const parts = text.split(regex)

  return parts.map((part, index) => {
    if (regex.test(part)) {
      return `<mark class="bg-yellow-400/50 text-yellow-900 dark:text-yellow-100 font-medium">${part}</mark>`
    }
    return part
  }).join('')
}

function toggleOpen() {
  open.value = !open.value
}

function selectOption(selectedValue: string) {
  internalValue.value = selectedValue === internalValue.value ? null : selectedValue
  open.value = false
  searchQuery.value = ''
}

// Fecha o dropdown ao clicar fora
onClickOutside(dropdownRef, (event) => {
  // Não fecha se o clique foi no trigger
  if (triggerRef.value) {
    const triggerEl = (triggerRef.value as any).$el as HTMLElement || triggerRef.value
    if (triggerEl && typeof triggerEl.contains === 'function' && triggerEl.contains(event.target as Node)) {
      return
    }
  }
  open.value = false
}, { ignore: [containerRef] })

function selectFirstFiltered() {
  if (filteredOptions.value.length > 0) {
    selectOption(getOptionValue(filteredOptions.value[0]))
  }
}

function focusFirstOption() {
  nextTick(() => {
    console.log('optionRefs', optionRefs.value)
    if (optionRefs.value[0]) {
      (optionRefs.value[0] as HTMLButtonElement).focus()
    }
  })
}

// Função de busca no backend
function performSearch(query: string) {
  if (!props.column.searchable) return

  isSearching.value = true
  console.log(optionsListRef.value ? optionsListRef.value : 'form')

  router.get(window.location.pathname, { [props.column.name]: query }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['form'],
    onSuccess: () => {
      // Após a busca, as opções serão atualizadas via watch nas props
      // Aguarda um tick para garantir que as props foram atualizadas
      nextTick(() => {
        isSearching.value = false
        const searchInputElement = document.getElementById(columnId.value + '_search') as HTMLInputElement
        if (searchInputElement) {
          setTimeout(() => {
            searchInputElement.focus()
          }, 100)
        }
      })
    },
    onError: (errors: any) => {
      console.error('Erro na busca:', errors)
      isSearching.value = false
    }
  })
}

// Debounce da busca
const debouncedSearch = useDebounceFn(
  (query: string) => {
    if (query.trim()) {
      performSearch(query)
    } else {
      // Quando limpa a busca, faz uma requisição vazia para limpar os resultados no backend
      performSearch('')
    }
  },
  props.column.searchDebounce || 300
)

// Watch na query de busca
watch(searchQuery, (newQuery) => {
  if (props.column.searchable) {
    debouncedSearch(newQuery)
  }
})

// Inicializa: se houver valor selecionado e for searchable, garante que a opção está disponível
onMounted(() => {
  if (props.column.searchable && internalValue.value && options.value.length > 0) {
    // Se há um valor selecionado, garante que está nas opções iniciais
    const selected = options.value.find(
      (option) => getOptionValue(option) === internalValue.value
    )
    if (selected && searchResults.value.length === 0) {
      // Se encontrou a opção selecionada e não há resultados de busca, carrega as opções iniciais
      searchResults.value = options.value
    }
  }
})

// Atualiza a largura do popover baseado no trigger
// Atualiza a posição e largura do dropdown
function updateDropdownPosition() {
  if (!triggerRef.value || !dropdownRef.value) return

  // Acessa o elemento HTML nativo do componente Button
  const triggerEl = (triggerRef.value as any).$el as HTMLElement || triggerRef.value
  if (!triggerEl || typeof triggerEl.getBoundingClientRect !== 'function') return

  const triggerRect = triggerEl.getBoundingClientRect()

  dropdownStyle.value = {
    width: `${triggerRect.width}px`,
    top: `${triggerRect.bottom + window.scrollY + 4}px`,
    left: `${triggerRect.left + window.scrollX}px`,
  }
}

// Limpa a busca quando o dropdown abre
watch(open, (isOpen) => {
  if (isOpen) {
    // NÃO limpa o searchQuery - preserva o estado
    // searchQuery.value = ''

    // Atualiza posição do dropdown
    nextTick(() => {
      updateDropdownPosition()

      // Se for searchable, carrega opções iniciais se não houver resultados e não houver busca ativa
      if (props.column.searchable && searchResults.value.length === 0 && !searchQuery.value.trim()) {
        searchResults.value = options.value
      }
    })

    // Atualiza posição quando a janela é redimensionada ou scrolla
    const handleResize = () => updateDropdownPosition()
    const handleScroll = () => updateDropdownPosition()

    window.addEventListener('resize', handleResize)
    window.addEventListener('scroll', handleScroll, true)

    // Remove listeners quando fecha
    const unwatch = watch(() => open.value, (newValue) => {
      if (!newValue) {
        window.removeEventListener('resize', handleResize)
        window.removeEventListener('scroll', handleScroll, true)
        unwatch()
      }
    })
  } else {
    // Limpa os resultados ao fechar apenas se não houver valor selecionado
    if (props.column.searchable && !internalValue.value) {
      searchResults.value = []
      // Limpa a busca apenas ao fechar
      searchQuery.value = ''
    }
  }
})
</script>