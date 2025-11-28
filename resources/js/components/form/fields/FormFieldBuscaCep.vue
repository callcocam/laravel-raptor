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
    <FieldDescription v-if="column.helpText || column.hint || column.tooltip">
      {{ column.helpText || column.hint || column.tooltip }}
    </FieldDescription>

    <div class="grid grid-cols-12 gap-4">
      <!-- Campo CEP --> 

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
  [key: string]: any;
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
  executeOnChange?: string;
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

const isSearching = ref(false);
const cepError = ref("");
const fieldValues = ref<Record<string, any>>({});

// Campos de endereço do backend
const addressFields = computed(() => props.column.fields || []);

// Campo que aciona a busca (definido via executeOnChange)
const executeOnChangeField = computed(() => props.column.executeOnChange || 'zip_code');

// Inicializa valores dos campos
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      addressFields.value.forEach((field) => {
        fieldValues.value[field.name] = newValue[field.name] || "";
      });
    }
  },
  { immediate: true }
);

// Watch para acionar busca quando o campo executeOnChange mudar
watch(
  () => fieldValues.value[executeOnChangeField.value],
  (newCep, oldCep) => {
    // Só busca se o CEP mudou e tem 8 dígitos
    if (newCep && newCep !== oldCep) {
      const cleaned = String(newCep).replace(/\D/g, '');
      if (cleaned.length === 8) {
        searchCep(cleaned);
      }
    }
  }
);

// Busca CEP na API ViaCEP
async function searchCep(cep: string) {
  // CEP já vem validado do watch (8 dígitos, sem formatação)
  isSearching.value = true;
  cepError.value = "";

  try {
    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    const data = await response.json();
    console.log("ViaCEP response:", data);

    if (data.erro) {
      cepError.value = "CEP não encontrado.";
      return;
    }

    // Usa o mapeamento definido no backend (obrigatório)
    const fieldMapping = props.column.fieldMapping;

    if (!fieldMapping) {
      console.error("Field mapping não definido no backend!");
      cepError.value = "Configuração de mapeamento ausente.";
      return;
    }

    console.log("Field mapping:", fieldMapping);
    console.log("Address fields:", addressFields.value);

    // Mapeia os campos da API para os campos do formulário
    Object.entries(fieldMapping).forEach(([apiField, formField]) => {
      const value = data[apiField] || "";
      console.log(`Mapping ${apiField} (${value}) -> ${formField}`);

      // Atualiza fieldValues primeiro
      fieldValues.value[formField] = value;
    });

    // Emite todos os valores de uma vez
    const updatedValues = {
      ...props.modelValue,
      ...fieldValues.value,
    };
    console.log("Emitting all address values:", updatedValues);
    emit("update:modelValue", updatedValues);
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
  console.log(`Emitting ${fieldName}:`, value, "Updated values:", updatedValues);
  emit("update:modelValue", updatedValues);
}

// Handle update de campo individual
function handleFieldUpdate(fieldName: string, value: any) {
  fieldValues.value[fieldName] = value;
  emitValue(fieldName, value);
}

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
