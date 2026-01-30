<!--
 * ModalForm - Renderiza formulário em modal com lógica de submit
 *
 * Componente especializado autocontido que gerencia:
 * - Estado do formulário (useForm do Inertia)
 * - Renderização dos campos
 * - Submit e validação
 * - Botões de ação
 -->
<template>
  <FormRenderer :columns="columns" :errors="form.errors" :gridColumns="gridColumns" :gap="gap" v-model="formData"
    ref="formRef" @submit="handleSubmit">
    <!-- Footer com botões -->
    <template #actions>
      <slot name="footer">
        <div class="flex justify-end gap-3 mt-6">
          <Button variant="outline" @click="emit('cancel')" :disabled="form.processing">
            Cancelar
          </Button>
          <Button @click="handleSubmit" :disabled="form.processing">
            {{ form.processing ? "Processando..." : confirmText }}
          </Button>
        </div>
      </slot>
    </template>
  </FormRenderer>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import FormRenderer from "../../form/FormRenderer.vue";

interface Props {
  columns: any[];
  modelValue: Record<string, any>;
  action: {
    url: string;
    method: string;
    confirm?: {
      confirmButtonText?: string;
      closeModalOnSuccess?: boolean;
    };
    inertia?: {
      only?: string[];
      preserveScroll?: boolean;
      preserveState?: boolean;
    };
  };
  confirmText?: string;
  gridColumns?: string;
  gap?: string;
}

const props = withDefaults(defineProps<Props>(), {
  confirmText: "Confirmar",
  gridColumns: "12",
  gap: "4",
});

const emit = defineEmits<{
  (e: "update:modelValue", value: Record<string, any>): void;
  (e: "success", data: any): void;
  (e: "error", errors: any): void;
  (e: "cancel"): void;
}>();
 

// Referência ao FormRenderer
const formRef = ref<InstanceType<typeof FormRenderer> | null>(null);

// Dados do formulário (ref separado para v-model)
const formData = ref<Record<string, any>>(props.modelValue);

// Form do Inertia - gerencia automaticamente processing, errors, success
const form = useForm({});

const inertiaConfig = computed(() =>
  props.action.inertia ||
  {
    only: [],
    preserveScroll: true,
    preserveState: false
  }
);

// Handler para submit do formulário
const handleSubmit = () => {
  // Submit usando useForm do Inertia com transform para passar os dados atualizados
  form
    .transform(() => formData.value)
    .submit(
      props.action.method.toLowerCase() as "post" | "put" | "patch" | "delete",
      props.action.url,
      {
        preserveScroll: inertiaConfig.value.preserveScroll,
        preserveState: inertiaConfig.value.preserveState,
        onSuccess: (page) => {
          emit("success", page);
        },
        onError: (errors) => {
          // form.errors já foi populado automaticamente pelo Inertia
          emit("error", errors);
        },
      }
    );
};

// Limpa erros do formulário
const clearErrors = () => {
  form.clearErrors();
};

defineExpose({
  formRef,
  formData,
  form,
  clearErrors,
});
</script>
