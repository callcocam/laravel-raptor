<!--
 * FormFieldMask - Text input field with mask support
 *
 * Supports custom masks for phone, CPF, CNPJ, date, etc.
 -->
<template>
  <Field orientation="vertical" :data-invalid="hasError" class="gap-y-1">
    <FieldLabel v-if="column.label" :for="column.name">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLabel>

    <Input
      :id="column.name"
      :name="column.name"
      type="text"
      :placeholder="column.placeholder || column.label"
      :required="column.required"
      :disabled="column.disabled"
      :readonly="column.readonly"
      :modelValue="maskedValue"
      @input="handleInput"
      @blur="handleBlur"
      :aria-invalid="hasError"
      :class="hasError ? 'border-destructive' : ''"
      :maxlength="maxLength"
    />

    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <FieldError :errors="errorArray" />
  </Field>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Input } from "@/components/ui/input";
import { Field, FieldLabel, FieldDescription, FieldError } from "@/components/ui/field";

interface FormColumn {
  name: string;
  label?: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  readonly?: boolean;
  tooltip?: string;
  helpText?: string;
  hint?: string;
  default?: string | number;
  mask?: string; // Ex: '(##) ####-####', '###.###.###-##', etc.
  maskTokens?: Record<string, RegExp>; // Custom tokens
}

interface Props {
  column: FormColumn;
  modelValue?: string | number | null;
  error?: string | string[];
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  error: undefined,
});

const emit = defineEmits<{
  (e: "update:modelValue", value: string | null): void;
}>();

const hasError = computed(() => !!props.error);

const errorArray = computed(() => {
  if (!props.error) return [];
  if (Array.isArray(props.error)) {
    return props.error.map((msg) => ({ message: msg }));
  }
  return [{ message: props.error }];
});

// Tokens padrão para máscaras
const defaultTokens: Record<string, RegExp> = {
  '#': /\d/, // Dígito
  'A': /[a-zA-Z]/, // Letra
  'N': /[a-zA-Z0-9]/, // Alfanumérico
  'X': /./,  // Qualquer caractere
};

// Calcula maxLength baseado na máscara
const maxLength = computed(() => {
  if (!props.column.mask) return undefined;
  return props.column.mask.length;
});

// Aplica máscara ao valor
function applyMask(value: string): string {
  if (!props.column.mask) return value;

  const mask = props.column.mask;
  const tokens = { ...defaultTokens, ...(props.column.maskTokens || {}) };
  
  // Remove caracteres não numéricos/alfabéticos
  const cleanValue = value.replace(/[^\w]/g, '');
  
  let masked = '';
  let valueIndex = 0;

  for (let i = 0; i < mask.length && valueIndex < cleanValue.length; i++) {
    const maskChar = mask[i];
    const token = tokens[maskChar];

    if (token) {
      // É um token de máscara
      const char = cleanValue[valueIndex];
      if (token.test(char)) {
        masked += char;
        valueIndex++;
      } else {
        // Caractere não corresponde ao token, pula
        break;
      }
    } else {
      // É um caractere fixo da máscara
      masked += maskChar;
    }
  }

  return masked;
}

// Valor com máscara aplicada
const maskedValue = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined) {
    return '';
  }
  return applyMask(String(props.modelValue));
});

// Handler de input
function handleInput(event: Event) {
  const input = event.target as HTMLInputElement;
  const masked = applyMask(input.value);
  
  // Emite o valor sem máscara (apenas números/letras)
  const unmasked = masked.replace(/[^\w]/g, '');
  emit('update:modelValue', unmasked || null);
  
  // Atualiza o cursor
  requestAnimationFrame(() => {
    input.value = masked;
  });
}

// Handler de blur
function handleBlur() {
  // Pode adicionar validações adicionais aqui se necessário
}
</script>
