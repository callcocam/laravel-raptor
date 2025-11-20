<!--
 * FieldWrapper - Wrapper para campos com prepend/append/prefix/suffix
 *
 * Renderiza addons antes e depois do campo com visual moderno
 -->
<template>
  <div class="space-y-1.5">
    <!-- Label -->
    <Label v-if="column.label" :for="column.name" class="font-medium">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive ml-1">*</span>
    </Label>

    <!-- Input Group com Prepend/Append -->
    <div v-if="hasPrependOrAppend" class="flex rounded-lg shadow-sm ring-1 ring-input hover:ring-ring transition-all">
      <!-- Prepend -->
      <div
        v-if="column.prepend || column.prefix"
        class="inline-flex items-center px-3 rounded-l-lg border-r border-input bg-muted/50 text-muted-foreground text-sm font-medium"
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
        class="inline-flex items-center px-3 rounded-r-lg border-l border-input bg-muted/50 text-muted-foreground text-sm font-medium"
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
    <div class="space-y-1.5">
      <!-- Error Message -->
      <div v-if="errorMessage" class="flex items-start gap-1.5 text-sm text-destructive font-medium">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Help Text -->
      <div v-if="column.helpText && !errorMessage" class="flex items-start gap-1.5 text-sm text-muted-foreground">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <span>{{ column.helpText }}</span>
      </div>

      <!-- Hint -->
      <p v-if="column.hint && !errorMessage" class="text-xs text-muted-foreground/80 italic pl-5">
        {{ column.hint }}
      </p>

      <!-- Tooltip (fallback se não houver helpText ou hint) -->
      <p v-if="column.tooltip && !column.helpText && !column.hint && !errorMessage" class="text-xs text-muted-foreground/80 pl-5">
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
