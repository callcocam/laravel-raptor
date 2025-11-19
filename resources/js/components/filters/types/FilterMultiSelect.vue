<!--
 * FilterMultiSelect - Filtro de múltipla seleção
 * 
 * Permite selecionar múltiplas opções com checkboxes
 * Mostra contador de itens selecionados
 * Inclui busca e botão "Limpar filtros"
 -->
<template>
  <div class="space-y-2">
    <Label>{{ filter.label }}</Label>
    <Popover>
      <PopoverTrigger as-child>
        <Button
          variant="outline"
          role="combobox"
          class="w-full justify-between"
        >
          <span v-if="selectedCount === 0" class="text-muted-foreground">
            {{ filter.placeholder || `Selecione ${filter.label.toLowerCase()}` }}
          </span>
          <span v-else>
            {{ selectedCount }} selecionado{{ selectedCount > 1 ? 's' : '' }}
          </span>
          <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-[300px] p-0" align="start">
        <!-- Search -->
        <div class="border-b p-2">
          <Input
            v-model="searchQuery"
            placeholder="Buscar..."
            class="h-8"
          />
        </div>

        <!-- Options List -->
        <div class="max-h-[300px] overflow-y-auto p-2">
          <div
            v-for="option in filteredOptions"
            :key="option.value"
            class="flex items-center space-x-2 py-2 px-2 rounded hover:bg-accent cursor-pointer"
            @click="toggleOption(option.value)"
          >
            <Checkbox
              :checked="isSelected(option.value)"
              @update:checked="toggleOption(option.value)"
            />
            <label class="flex-1 cursor-pointer text-sm">
              {{ option.label }}
            </label>
            <span v-if="option.count" class="text-xs text-muted-foreground">
              {{ option.count }}
            </span>
          </div>

          <div v-if="filteredOptions.length === 0" class="py-6 text-center text-sm text-muted-foreground">
            Nenhuma opção encontrada
          </div>
        </div>

        <!-- Footer -->
        <div v-if="selectedCount > 0" class="border-t p-2">
          <Button
            variant="ghost"
            size="sm"
            class="w-full"
            @click="clearSelection"
          >
            Limpar filtros
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { ChevronDown } from 'lucide-vue-next'

interface FilterOption {
  value: string | number
  label: string
  count?: number // Contador opcional como na imagem
}

interface Props {
  filter: {
    name: string
    label: string
    placeholder?: string
    options: FilterOption[]
    [key: string]: any
  }
  modelValue?: Array<string | number>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: Array<string | number>): void
}>()

// Estado interno
const searchQuery = ref('')
const selectedValues = ref<Array<string | number>>(props.modelValue || [])

// Sincroniza com modelValue quando muda externamente
watch(() => props.modelValue, (newValue) => {
  selectedValues.value = newValue || []
})

// Opções filtradas pela busca
const filteredOptions = computed(() => {
  if (!searchQuery.value) {
    return props.filter.options
  }

  const query = searchQuery.value.toLowerCase()
  return props.filter.options.filter(option =>
    option.label.toLowerCase().includes(query)
  )
})

// Contador de selecionados
const selectedCount = computed(() => selectedValues.value.length)

/**
 * Verifica se uma opção está selecionada
 */
const isSelected = (value: string | number): boolean => {
  return selectedValues.value.includes(value)
}

/**
 * Alterna seleção de uma opção
 */
const toggleOption = (value: string | number) => {
  const index = selectedValues.value.indexOf(value)
  
  if (index > -1) {
    // Remove se já está selecionado
    selectedValues.value = selectedValues.value.filter(v => v !== value)
  } else {
    // Adiciona se não está selecionado
    selectedValues.value = [...selectedValues.value, value]
  }

  // Emite mudança
  emit('update:modelValue', selectedValues.value)
}

/**
 * Limpa todas as seleções
 */
const clearSelection = () => {
  selectedValues.value = []
  emit('update:modelValue', [])
}
</script>
