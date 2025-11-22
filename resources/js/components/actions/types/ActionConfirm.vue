<!--
 * ActionConfirm - Componente de ação com confirmação
 *
 * Exibe um botão que, ao clicar, mostra um modal de confirmação
 * antes de executar a ação
 *
 * Usa AlertDialog da shadcn-vue para seguir o padrão do projeto
 -->
<template>
  <AlertDialog v-model:open="isOpen">
    <AlertDialogTrigger as-child>
      <Button :variant="variant" :size="size" class="gap-1 h-7 px-2.5">
        <component v-if="iconComponent" :is="iconComponent" class="h-3 w-3" />
        <span class="text-xs">{{ action.label }}</span>
      </Button>
    </AlertDialogTrigger>

    <AlertDialogContent>
      <div class="flex flex-col items-center gap-4 py-4">
        <component :is="questionIcon" class="h-16 w-16 text-muted-foreground" />

        <AlertDialogHeader class="text-center space-y-2">
          <AlertDialogTitle class="text-center">
            {{ confirmConfig.title || "Confirmar Ação" }}
          </AlertDialogTitle>
          <AlertDialogDescription class="text-center">
            {{
              confirmConfig.message ||
              confirmConfig.text ||
              "Tem certeza que deseja executar esta ação?"
            }}
          </AlertDialogDescription>
        </AlertDialogHeader>

        <!-- Campo de confirmação por digitação -->
        <div v-if="requiresTypedConfirmation" class="w-full px-6">
          <label class="block text-sm font-medium mb-2 text-center">
            Digite <strong>{{ typedConfirmationWord }}</strong> para confirmar:
          </label>
          <input
            v-model="typedWord"
            type="text"
            :placeholder="typedConfirmationWord"
            class="w-full px-3 py-2 border rounded-md text-center focus:outline-none focus:ring-2 focus:ring-primary"
            @keyup.enter="isTypedWordCorrect && !isSubmitting && confirmAction()"
          />
          <p v-if="showTypedError" class="text-sm text-destructive mt-2 text-center">
            A palavra digitada não corresponde
          </p>
        </div>
      </div>

      <AlertDialogFooter class="flex justify-center gap-2 w-full items-center">
        <div class="flex w-full justify-center space-x-4">
          <AlertDialogCancel :disabled="isSubmitting">
            {{ confirmConfig.cancelText || confirmConfig.cancelButtonText || "Cancelar" }}
          </AlertDialogCancel>
          <AlertDialogAction
            :class="confirmVariantClass"
            @click="confirmAction"
            :disabled="isSubmitting || (requiresTypedConfirmation && !isTypedWordCorrect)"
          >
            {{
              isSubmitting
                ? "Processando..."
                : confirmConfig.confirmText ||
                  confirmConfig.confirmButtonText ||
                  "Confirmar"
            }}
          </AlertDialogAction>
        </div>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { ref, computed, h } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import * as LucideIcons from "lucide-vue-next";
import type { TableAction } from "~/types/table";

interface Props {
  action: TableAction;
  size?: "default" | "sm" | "lg" | "icon";
}

const props = withDefaults(defineProps<Props>(), {
  size: "sm",
});

const emit = defineEmits<{
  (e: "click"): void;
  (e: "success", data: any): void;
  (e: "error", error: any): void;
}>();

// Estado do dialog
const isOpen = ref(false);

// Estado para confirmação por digitação
const typedWord = ref('');
const showTypedError = ref(false);

// Form do Inertia - gerencia automaticamente processing, errors, success
const form = useForm({
  actionType: '',
  actionName: ''
});

// Alias para manter compatibilidade no template
const isSubmitting = computed(() => form.processing);

// Configuração de confirmação
const confirmConfig = computed(() => {
  const config = props.action.confirm || true;
  
  // Se confirm for boolean true, retorna configuração padrão
  if (config === true || !config) {
    return {
      title: "",
      message: "",
      confirmText: "",
      cancelText: "",
      confirmColor: "",
      text: "",
      confirmButtonText: "",
      cancelButtonText: "",
      successMessage: "",
      requiresTypedConfirmation: false,
      typedConfirmationWord: "EXCLUIR",
    };
  }
  
  // Se for array ou objeto, retorna com valores padrão
  return {
    title: config.title || "",
    message: config.message || "",
    confirmText: config.confirmText || "",
    cancelText: config.cancelText || "",
    confirmColor: config.confirmColor || "",
    text: config.text || "",
    confirmButtonText: config.confirmButtonText || "",
    cancelButtonText: config.cancelButtonText || "",
    successMessage: config.successMessage || "",
    requiresTypedConfirmation: config.requiresTypedConfirmation || false,
    typedConfirmationWord: config.typedConfirmationWord || "EXCLUIR",
  };
});

// Verifica se requer confirmação por digitação
const requiresTypedConfirmation = computed(() => {
  return confirmConfig.value.requiresTypedConfirmation === true;
});

// Palavra que deve ser digitada
const typedConfirmationWord = computed(() => {
  return confirmConfig.value.typedConfirmationWord || 'EXCLUIR';
});

// Verifica se a palavra digitada está correta
const isTypedWordCorrect = computed(() => {
  if (!requiresTypedConfirmation.value) return true;
  return typedWord.value.toUpperCase() === typedConfirmationWord.value.toUpperCase();
});

// Mapeia cor para variant do shadcn (botão principal)
const variant = computed(() => {
  const colorMap: Record<string, any> = {
    green: "default",
    blue: "default",
    red: "destructive",
    yellow: "warning",
    gray: "secondary",
    default: "default",
  };

  return colorMap[props.action.color || "default"] || "default";
});

// Classes para o botão de confirmação
const confirmVariantClass = computed(() => {
  const color = confirmConfig.value.confirmColor || props.action.color || "default";
  const variantMap: Record<string, string> = {
    red: "bg-destructive text-destructive-foreground hover:bg-destructive/90",
    green: "bg-primary text-primary-foreground hover:bg-primary/90",
    blue: "bg-primary text-primary-foreground hover:bg-primary/90",
    yellow: "bg-yellow-500 text-white hover:bg-yellow-600",
    gray: "bg-secondary text-secondary-foreground hover:bg-secondary/80",
  };

  return variantMap[color] || "";
});

// Componente do ícone dinâmico
const iconComponent = computed(() => {
  if (!props.action.icon) return null;

  const IconComponent = (LucideIcons as any)[props.action.icon];

  if (!IconComponent) {
    console.warn(`Icon "${props.action.icon}" not found in lucide-vue-next`);
    return null;
  }

  return h(IconComponent);
});

// Ícone padrão de question para o modal
const questionIcon = computed(() => {
  const QuestionIcon = (LucideIcons as any)["CircleHelp"];
  return h(QuestionIcon);
});

// Confirma a ação
const confirmAction = () => {
  // Verifica confirmação por digitação se necessário
  if (requiresTypedConfirmation.value && !isTypedWordCorrect.value) {
    showTypedError.value = true;
    return;
  }

  // Atualiza o form com os dados da action
  form.actionType = props.action.actionType || '';
  form.actionName = props.action.name || '';

  // Submit usando useForm do Inertia - processing e errors são gerenciados automaticamente
  form.submit(
    props.action.method.toLowerCase() as "post" | "put" | "patch" | "delete",
    props.action.url,
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: (page) => {
        emit("success", page);
        isOpen.value = false;
        // Reseta palavra digitada
        typedWord.value = '';
        showTypedError.value = false;

        // Emite evento de click para compatibilidade
        emit("click");
      },
      onError: (errors) => {
        emit("error", errors);
      }
    }
  );
};
</script>
