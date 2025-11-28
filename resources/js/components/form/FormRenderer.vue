<!--
 * FormRenderer - Renderiza um formulário completo
 *
 * Recebe um objeto de formulário com colunas e renderiza todos os campos
 * dinamicamente usando o FieldRenderer com suporte a grid layout
 -->
<template>
  <Form @submit="handleSubmit" :class="formClasses">
    <div
      v-for="(column, index) in columns"
      :key="column.name || index"
      :class="getColumnClasses(column)"
      :style="getColumnStyles(column)"
    >
      <FieldRenderer
        :column="column"
        :index="index"
        :error="formErrors[column.name]"
        :modelValue="formData[column.name]"
        @update:modelValue="(value) => handleFieldUpdate(column.name, value)"
      />
    </div>

    <!-- Slot para botões customizados -->
    <div class="col-span-full">
      <slot name="actions" :formData="formData" :isValid="isValid" :errors="formErrors">
        <!-- Botões padrão (opcional) -->
      </slot>
    </div>
  </Form>
</template>

<script setup lang="ts">
import { computed, provide } from "vue";
import { Form } from "@inertiajs/vue3";
import FieldRenderer from "./columns/FieldRenderer.vue";
import { useGridLayout } from "~/composables/useGridLayout";

interface FormColumn {
  name: string;
  label?: string;
  component?: string;
  required?: boolean;
  columnSpan?: string;
  gridColumns?: string;
  order?: number;
  gap?: string;
  responsive?: {
    grid?: { sm?: string; md?: string; lg?: string; xl?: string };
    span?: { sm?: string; md?: string; lg?: string; xl?: string };
  };
  [key: string]: any;
}

interface Props {
  columns?: FormColumn[];
  modelValue?: any; // Inertia form instance ou objeto simples
  errors?: Record<string, string | string[]>;
  gridColumns?: string; // Número de colunas do grid do formulário (padrão: 12)
  gap?: string; // Espaçamento entre campos (padrão: 4)
  action?: string; // URL para submit (se não fornecido, usa comportamento legacy com evento @submit)
  method?: "get" | "post" | "put" | "patch" | "delete"; // Método HTTP
}

const props = withDefaults(defineProps<Props>(), {
  columns: () => [],
  modelValue: () => ({}),
  errors: () => ({}),
  gridColumns: "12",
  gap: "4",
  action: undefined,
  method: "post",
});

const emit = defineEmits<{
  (e: "update:modelValue", value: any): void;
  (e: "submit"): void; // Legacy: emitido quando action não é fornecido
  (e: "before-submit", payload: { data: any; cancel: () => void }): void;
  (e: "success", response: any): void;
  (e: "error", errors: Record<string, string | string[]>): void;
}>();

// Grid layout composable
const { formClasses, getColumnClasses, getColumnStyles } = useGridLayout({
  gridColumns: props.gridColumns,
  gap: props.gap,
});

// Referência ao form data (pode ser Inertia form ou objeto reativo)
const formData = computed(() => props.modelValue);

// Provê formData para componentes filhos (para cálculos)
provide('formData', formData);

// Handler para atualização de campos
const handleFieldUpdate = (fieldName: string, value: any) => {
  console.log('Updating field:', fieldName, 'to value:', value);
  
  if (props.modelValue && typeof props.modelValue === "object") {
    // Sempre atribui diretamente ao campo
    // eslint-disable-next-line vue/no-mutating-props
    props.modelValue[fieldName] = value;
  }
};

// Erros de validação
const formErrors = computed(() => {
  // Se for um Inertia form, use form.errors, senão use a prop errors
  return props.modelValue?.errors || props.errors || {};
});

// Validação básica
const isValid = computed(() => {
  // Verifica se todos os campos obrigatórios estão preenchidos
  return props.columns.every((column) => {
    if (column.required) {
      const value = formData.value[column.name];
      return value !== null && value !== undefined && value !== "";
    }
    return true;
  });
});

// Verifica se há arquivos no formData (recursivamente)
const hasFiles = (obj: any = formData.value): boolean => {
  if (!obj || typeof obj !== "object") return false;

  // Verifica File
  if (obj instanceof File) return true;

  // Verifica FileList
  if (obj instanceof FileList) return obj.length > 0;

  // Verifica Blob
  if (obj instanceof Blob) return true;

  // Verifica arrays
  if (Array.isArray(obj)) {
    return obj.some((item) => hasFiles(item));
  }

  // Verifica objetos recursivamente
  return Object.values(obj).some((value) => hasFiles(value));
};

// Handler de submit
const handleSubmit = () => {
  // Modo legacy: se não tiver action, apenas emite evento para o pai tratar
  if (!props.action) {
    emit("submit");
    return;
  }

  // Modo auto-suficiente: executa submit internamente
  let cancelled = false;

  // Emite evento before-submit (permite cancelamento)
  emit("before-submit", {
    data: formData.value,
    cancel: () => {
      cancelled = true;
    },
  });

  if (cancelled) return;

  const hasFileUploads = hasFiles();
  const method = props.method.toLowerCase();

  // Sempre usa POST para métodos que não são GET
  // Para PUT/PATCH/DELETE, usa method spoofing com _method
  if (method !== "post" && method !== "get") {
    formData.value
      .transform((data: Record<string, any>) => {
        // Remove campos password vazios em edição (PUT/PATCH)
        const cleanData = { ...data };
        Object.keys(cleanData).forEach(key => {
          if (key.includes('password') && !cleanData[key]) {
            delete cleanData[key];
          }
        });

        return {
          ...cleanData,
          _method: method.toUpperCase(),
        };
      })
      .post(props.action, {
        preserveScroll: true,
        preserveState: true,
        ...(hasFileUploads ? { forceFormData: true } : {}),
        onSuccess: (response: any) => {
          emit("success", response);
        console.log(response);
        },
        onError: (errors: Record<string, any>) => {
          console.error(errors);
          emit("error", errors);
        },
      });
  } else {
    // POST ou GET: usa método HTTP nativo
    const submitMethod = method as "get" | "post";

    formData.value[submitMethod](props.action, {
      preserveScroll: true,
      preserveState: true,
      ...(hasFileUploads ? { forceFormData: true } : {}),
      onSuccess: (response: any) => {
        emit("success", response);
        console.log(response);
      },
      onError: (errors: Record<string, any>) => {
        console.error(errors);
        emit("error", errors);
      },
    });
  }
};

// Expõe métodos para controle externo
defineExpose({
  formData: formData,
  isValid,
  errors: formErrors,
  submit: handleSubmit,
});
</script>
