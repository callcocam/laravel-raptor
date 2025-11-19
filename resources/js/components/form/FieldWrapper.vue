<!--
 * FieldWrapper - Wrapper para campos com prepend/append/prefix/suffix
 *
 * Renderiza addons antes e depois do campo
 -->
<template>
  <div class="space-y-2">
    <!-- Label -->
    <Label v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </Label>

    <!-- Input Group com Prepend/Append -->
    <div v-if="hasPrependOrAppend" class="flex rounded-md shadow-sm">
      <!-- Prepend -->
      <div
        v-if="column.prepend || column.prefix"
        class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-input bg-muted text-muted-foreground text-sm"
      >
        <component
          v-if="prependIcon"
          :is="prependIcon"
          class="h-4 w-4"
        />
        <span v-else>{{ column.prepend || column.prefix }}</span>
      </div>

      <!-- Slot para o campo -->
      <div class="flex-1">
        <slot />
      </div>

      <!-- Append -->
      <div
        v-if="column.append || column.suffix"
        class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-input bg-muted text-muted-foreground text-sm"
      >
        <component
          v-if="appendIcon"
          :is="appendIcon"
          class="h-4 w-4"
        />
        <span v-else>{{ column.append || column.suffix }}</span>
      </div>
    </div>

    <!-- Campo sem addons -->
    <div v-else>
      <slot />
    </div>

    <!-- Mensagens de ajuda e erro -->
    <div class="space-y-1">
      <!-- Error Message -->
      <p v-if="errorMessage" class="text-sm text-destructive font-medium">
        {{ errorMessage }}
      </p>

      <!-- Help Text -->
      <p v-if="column.helpText && !errorMessage" class="text-sm text-muted-foreground">
        {{ column.helpText }}
      </p>

      <!-- Hint -->
      <p v-if="column.hint && !errorMessage" class="text-xs text-muted-foreground italic">
        {{ column.hint }}
      </p>

      <!-- Tooltip (fallback se não houver helpText ou hint) -->
      <p v-if="column.tooltip && !column.helpText && !column.hint && !errorMessage" class="text-xs text-muted-foreground">
        {{ column.tooltip }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import { Label } from '@/components/ui/label'
import * as LucideIcons from 'lucide-vue-next'

interface Props {
  column: {
    name: string
    label?: string
    required?: boolean
    prepend?: string
    append?: string
    prefix?: string
    suffix?: string
    helpText?: string
    hint?: string
    tooltip?: string
  }
  error?: string | string[]
}

const props = defineProps<Props>()

const hasPrependOrAppend = computed(() => {
  return !!(props.column.prepend || props.column.append || props.column.prefix || props.column.suffix)
})

// Verifica se prepend/append é um ícone do Lucide
const prependIcon = computed(() => {
  if (!props.column.prepend) return null
  const IconComponent = (LucideIcons as any)[props.column.prepend]
  return IconComponent ? h(IconComponent) : null
})

const appendIcon = computed(() => {
  if (!props.column.append) return null
  const IconComponent = (LucideIcons as any)[props.column.append]
  return IconComponent ? h(IconComponent) : null
})

// Converte error (string | string[]) para mensagem única
const errorMessage = computed(() => {
  if (!props.error) return null
  if (Array.isArray(props.error)) {
    return props.error[0] || null
  }
  return props.error
})
</script>
