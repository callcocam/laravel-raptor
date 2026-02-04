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
      <!-- Campos de endereço dinâmicos -->
      <div
        v-for="(field, index) in addressFields"
        :key="field.name"
        :class="getCepFieldClass(field)"
      >
        <!-- Campo CEP com botão -->
        <div v-if="field.name === executeOnChangeField" class="flex gap-2">
          <div class="relative flex-1">
            <FieldRenderer
              :column="field"
              :index="index"
              :error="cepError ? cepError : props.error?.[field.name]"
              :modelValue="fieldValues[field.name]"
              @update:modelValue="(value) => handleFieldUpdate(field.name, value)"
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

          <Button
            type="button"
            variant="outline"
            size="default"
            @click="handleSearchClick"
            :disabled="!canSearch || isSearching"
            class="shrink-0 mt-6"
          >
            <Search class="h-4 w-4 mr-2" /> 
          </Button>
        </div>

        <!-- Outros campos normais -->
        <FieldRenderer
          v-else
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
import { Button } from "@/components/ui/button";
import { Search } from "lucide-vue-next";

import {
  FieldLegend,
  FieldSet,
  FieldDescription,
} from "@/components/ui/field";
import FieldRenderer from "../FieldRenderer.vue";
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
  modelValue?: Record<string, any> | string | null;
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

// Pode buscar se tiver 8 dígitos
const canSearch = computed(() => {
  const cepValue = fieldValues.value[executeOnChangeField.value];
  if (!cepValue) return false;
  const cleaned = String(cepValue).replace(/\D/g, '');
  return cleaned.length === 8;
});

// Retorna classe do campo, ajustando para o campo CEP que tem botão
const getCepFieldClass = (field: AddressField) => {
  // Se for o campo CEP, não usa a classe de grid pois o flex já controla
  if (field.name === executeOnChangeField.value) {
    return getColumnClasses(field);
  }
  return getColumnClasses(field);
};

// Inicializa valores dos campos
watch(
  () => props.modelValue,
  (newValue) => {
    // Normaliza o modelValue para sempre ser um objeto
    const normalizedValue = typeof newValue === 'object' && newValue !== null ? newValue : {};
    
    addressFields.value.forEach((field) => {
      fieldValues.value[field.name] = normalizedValue[field.name] || "";
    });
  },
  { immediate: true }
);

// Handler de clique no botão buscar
const handleSearchClick = () => {
  const cepValue = fieldValues.value[executeOnChangeField.value];
  if (cepValue) {
    const cleaned = String(cepValue).replace(/\D/g, '');
    if (cleaned.length === 8) {
      searchCep(cleaned);
    } else {
      cepError.value = "CEP deve ter 8 dígitos.";
    }
  }
};

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

    console.log("Field mapping:", fieldMapping);

    if (!fieldMapping) { 
      cepError.value = "Configuração de mapeamento ausente.";
      return;
    }
 

    // Mapeia os campos da API para os campos do formulário
    Object.entries(fieldMapping).forEach(([apiField, formField]) => {
      const value = data[apiField] || "";
      console.log(`Mapping ${apiField} (${value}) -> ${formField}`);

      // Atualiza fieldValues primeiro
      fieldValues.value[formField] = value;
    });

    // Emite todos os valores de uma vez
    const currentValue = typeof props.modelValue === 'object' && props.modelValue !== null ? props.modelValue : {};
    
    const updatedValues = {
      ...currentValue,
      ...fieldValues.value,
    }; 
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
  // Normaliza o modelValue para sempre ser um objeto
  const currentValue = typeof props.modelValue === 'object' && props.modelValue !== null ? props.modelValue : {};
  
  const updatedValues = {
    ...currentValue,
    [fieldName]: value,
  }; 
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
