<!--
 * FormFieldCombobox - Combobox/autocomplete field using shadcn-vue Field primitives
 *
 * Searchable select with autocomplete functionality
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
          :aria-invalid="hasError"
          :class="[
            'w-full justify-between',
            hasError ? 'border-destructive' : '',
            !selectedOption && 'text-muted-foreground'
          ]"
        >
          {{ selectedOption?.label || column.placeholder || 'Selecione...' }}
          <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent class="w-full p-0" align="start">
        <Command>
          <CommandInput 
            class="h-9" 
            :placeholder="column.searchPlaceholder || 'Buscar...'" 
          />
          <CommandList>
            <CommandEmpty>{{ column.emptyText || 'Nenhum resultado encontrado.' }}</CommandEmpty>
            <CommandGroup>
              <CommandItem
                v-for="option in options"
                :key="getOptionValue(option)"
                :value="getOptionValue(option)"
                @select="(ev) => selectOption(ev.detail.value as string)"
              >
                {{ getOptionLabel(option) }}
                <CheckIcon
                  :class="
                    cn(
                      'ml-auto h-4 w-4',
                      internalValue === getOptionValue(option) 
                        ? 'opacity-100' 
                        : 'opacity-0'
                    )
                  "
                />
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
import { computed, ref } from 'vue'
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Field, FieldLabel, FieldDescription, FieldError } from '@/components/ui/field'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'

interface ComboboxOption {
  label?: string
  value?: string | number
  [key: string]: any
}

interface FormColumn {
  name: string
  label?: string
  placeholder?: string
  searchPlaceholder?: string
  emptyText?: string
  required?: boolean
  options?: ComboboxOption[] | Record<string, string>
  tooltip?: string
  helpText?: string
  hint?: string
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

const open = ref(false)

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

const internalValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const selectedOption = computed(() =>
  options.value.find(
    (option) => getOptionValue(option) === internalValue.value
  )
)

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

function selectOption(selectedValue: string) {
  internalValue.value = selectedValue === internalValue.value ? null : selectedValue
  open.value = false
}
</script>
