<!--
 * FormFieldBuscaCep - CEP lookup field with automatic address filling
 *
 * Integrates with ViaCEP API to automatically fill address fields
 -->
<template>
  <FieldSet orientation="vertical" class="gap-y-4">
    <FieldLegend v-if="column.label">
      {{ column.label }}
      <span v-if="column.required" class="text-destructive">*</span>
    </FieldLegend>
    <FieldDescription    v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <div class="grid grid-cols-12 gap-4">
      <!-- Campo CEP -->
      <Field
        orientation="vertical"
        :data-invalid="hasError(column.name)"
        class="col-span-12 md:col-span-4 gap-y-1"
      >
        <FieldLabel :for="column.name">
          CEP
          <span v-if="column.required" class="text-destructive">*</span>
        </FieldLabel>

        <div class="relative">
          <Input
            :id="column.name"
            :name="column.name"
            type="text"
            v-model="cepValue"
            @input="handleCepInput"
            @blur="searchCep"
            placeholder="00000-000"
            maxlength="9"
            :disabled="isSearching"
            :aria-invalid="hasError(column.name)"
            :class="hasError(column.name) ? 'border-destructive' : ''"
          />
          <div v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
            <svg
              class="animate-spin h-4 w-4 text-muted-foreground"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
          </div>
        </div>

        <FieldError v-if="cepError" :errors="[{ message: cepError }]" />
        <FieldError v-else :errors="getErrorArray(column.name)" />
      </Field>

      <!-- Campos de endereço dinâmicos -->
      <div
        v-for="(field, index) in addressFields"
        :key="field.name"
        :class="getColumnClasses(field)"
      >
        <FieldRenderer
          :column="field"
          :index="index"
          :error="props.error?.[field.name]"
          :modelValue="fieldValues[field.name]"
          @update:modelValue="(value) => handleFieldUpdate(field.name, value)"
        />
      </div>
    </div>
  </FieldSet>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Input } from "@/components/ui/input";

import {  
  FieldLegend, 
  FieldSet,  
  Field,
  FieldLabel,
  FieldError,
  FieldDescription,
} from "@/components/ui/field"; 
import FieldRenderer from "../columns/FieldRenderer.vue";
import { useGridLayout } from "~/composables/useGridLayout";

interface AddressField {
  name: string;
  label: string;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  readonly?: boolean;
  helpText?: string;
  columnSpan?: string;
}

interface FormColumn {
  name: string;
  label?: string;
  required?: boolean;
  tooltip?: string;
  helpText?: string;
  hint?: string;
  fields?: AddressField[];
  fieldMapping?: Record<string, string>;
}

interface Props {
  column: FormColumn;
  modelValue?: Record<string, any>;
  error?: Record<string, string | string[]>;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => ({}),
  error: () => ({}),
});

const emit = defineEmits<{
  (e: "update:modelValue", value: Record<string, any>): void;
}>();

// Grid layout composable
const { getColumnClasses } = useGridLayout();

const cepValue = ref("");
const isSearching = ref(false);
const cepError = ref("");
const fieldValues = ref<Record<string, any>>({});

// Campos de endereço do backend
const addressFields = computed(() => props.column.fields || []);

// Inicializa valores dos campos
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      cepValue.value = formatCep(newValue[props.column.name] || "");
      addressFields.value.forEach((field) => {
        fieldValues.value[field.name] = newValue[field.name] || "";
      });
    }
  },
  { immediate: true }
);

// Formata CEP com máscara
function formatCep(value: string): string {
  const cleaned = value.replace(/\D/g, "");
  if (cleaned.length <= 5) return cleaned;
  return `${cleaned.slice(0, 5)}-${cleaned.slice(5, 8)}`;
}

// Handle input do CEP
function handleCepInput(event: Event) {
  const input = event.target as HTMLInputElement;
  const formatted = formatCep(input.value);
  cepValue.value = formatted;

  // Emite o valor sem formatação
  emitValue(props.column.name, formatted.replace(/\D/g, ""));
}

// Busca CEP na API ViaCEP
async function searchCep() {
  const cep = cepValue.value.replace(/\D/g, "");

  // Valida CEP
  if (!cep) {
    cepError.value = "";
    return;
  }

  if (cep.length !== 8) {
    cepError.value = "CEP inválido. Digite 8 dígitos.";
    return;
  }

  isSearching.value = true;
  cepError.value = "";

  try {
    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    const data = await response.json();
    console.log(data);
    if (data.erro) {
      cepError.value = "CEP não encontrado.";
      return;
    }

    // Usa o mapeamento definido no backend ou padrão
    const fieldMapping = props.column.fieldMapping || {
      logradouro: 'street',
      bairro: 'neighborhood',
      localidade: 'city',
      uf: 'state',
      complemento: 'complement',
    };

    // Mapeia os campos da API para os campos do formulário
    Object.entries(fieldMapping).forEach(([apiField, formField]) => {
      const value = data[apiField] || "";
      
      if (fieldValues.value.hasOwnProperty(formField)) {
        fieldValues.value[formField] = value;
        emitValue(formField, value);
      }
    });
  } catch (error) {
    console.error("Erro ao buscar CEP:", error);
    cepError.value = "Erro ao buscar CEP. Tente novamente.";
  } finally {
    isSearching.value = false;
  }
}

// Emite valor atualizado
function emitValue(fieldName: string, value: any) {
  const updatedValues = {
    ...props.modelValue,
    [fieldName]: value,
  };
  emit("update:modelValue", updatedValues);
}

// Handle update de campo individual
function handleFieldUpdate(fieldName: string, value: any) {
  fieldValues.value[fieldName] = value;
  emitValue(fieldName, value);
}

// Watch para mudanças nos campos
watch(
  fieldValues,
  (newValues) => {
    Object.keys(newValues).forEach((fieldName) => {
      emitValue(fieldName, newValues[fieldName]);
    });
  },
  { deep: true }
);

// Verifica se campo tem erro
function hasError(fieldName: string): boolean {
  return !!(props.error && props.error[fieldName]);
}

// Retorna array de erros formatado
function getErrorArray(fieldName: string): Array<{ message: string }> {
  if (!props.error || !props.error[fieldName]) return [];

  const error = props.error[fieldName];
  if (Array.isArray(error)) {
    return error.map((msg) => ({ message: msg }));
  }
  return [{ message: error }];
}
</script>
