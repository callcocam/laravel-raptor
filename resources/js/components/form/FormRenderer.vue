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

// Referência ao form data (pode ser Inertia form ou objeto reativo)
const formData = computed(() => props.modelValue);

// Provê formData para componentes filhos (para cálculos)
provide('formData', formData);

// Handler para atualização de campos
const handleFieldUpdate = (fieldName: string, value: any) => {
  // Se for um Inertia form, usar a sintaxe correta
  if (props.modelValue && typeof props.modelValue === "object") {
    // Atualizar diretamente no objeto (Inertia form é reativo e espera mutação in-place)
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

// Classes do formulário (grid layout)
const formClasses = computed(() => {
  const gridColsMap: Record<string, string> = {
    "1": "md:grid-cols-1",
    "2": "md:grid-cols-2",
    "3": "md:grid-cols-3",
    "4": "md:grid-cols-4",
    "5": "md:grid-cols-5",
    "6": "md:grid-cols-6",
    "7": "md:grid-cols-7",
    "8": "md:grid-cols-8",
    "9": "md:grid-cols-9",
    "10": "md:grid-cols-10",
    "11": "md:grid-cols-11",
    "12": "md:grid-cols-12",
  };

  const gapMap: Record<string, string> = {
    "0": "gap-x-0",
    "1": "gap-x-1",
    "2": "gap-x-2",
    "3": "gap-x-3",
    "4": "gap-x-4",
    "5": "gap-x-5",
    "6": "gap-x-6",
    "8": "gap-x-8",
  };

  return [
    "grid",
    "grid-cols-1",
    gridColsMap[props.gridColumns] || "md:grid-cols-12",
    gapMap[props.gap] || "gap-x-4",
    "gap-y-4", // Espaçamento vertical entre campos
  ].join(" ");
});

// Gera classes do grid para cada coluna
const getColumnClasses = (column: FormColumn) => {
  const classes: string[] = [];

  const colSpanMap: Record<string, string> = {
    "1": "md:col-span-1",
    "2": "md:col-span-2",
    "3": "md:col-span-3",
    "4": "md:col-span-4",
    "5": "md:col-span-5",
    "6": "md:col-span-6",
    "7": "md:col-span-7",
    "8": "md:col-span-8",
    "9": "md:col-span-9",
    "10": "md:col-span-10",
    "11": "md:col-span-11",
    "12": "md:col-span-12",
    full: "md:col-span-full",
  };

  // Mobile: sempre full width (col-span-1 dentro de grid-cols-1)
  classes.push("col-span-1");

  // Column span padrão (desktop - md:)
  if (column.columnSpan) {
    const spanClass = colSpanMap[column.columnSpan];
    if (spanClass) {
      classes.push(spanClass);
    }
  }

  // Column span responsivo
  if (column.responsive?.span) {
    const { sm, md, lg, xl } = column.responsive.span;
    if (sm && colSpanMap[sm]) classes.push(colSpanMap[sm].replace("md:", "sm:"));
    if (md && colSpanMap[md]) classes.push(colSpanMap[md]);
    if (lg && colSpanMap[lg]) classes.push(colSpanMap[lg].replace("md:", "lg:"));
    if (xl && colSpanMap[xl]) classes.push(colSpanMap[xl].replace("md:", "xl:"));
  }

  return classes.join(" ");
};

// Gera estilos inline para ordem (se especificado)
const getColumnStyles = (column: FormColumn) => {
  if (column.order !== undefined) {
    return { order: column.order };
  }
  return {};
};

// Expõe métodos para controle externo
defineExpose({
  formData: formData,
  isValid,
  errors: formErrors,
  submit: handleSubmit,
});
</script>
