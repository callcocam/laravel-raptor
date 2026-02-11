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
      <Button :variant="variant" :size="computedSize" class="gap-1.5 btn-gradient">
        <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
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
          <input v-model="typedWord" type="text" :placeholder="typedConfirmationWord"
            class="w-full px-3 py-2 border rounded-md text-center focus:outline-none focus:ring-2 focus:ring-primary"
            @keyup.enter="isTypedWordCorrect && !isSubmitting && confirmAction()" />
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
          <AlertDialogAction :class="confirmVariantClass" @click="confirmAction"
            :disabled="isSubmitting || (requiresTypedConfirmation && !isTypedWordCorrect)">
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
import { ref, computed, h, watch } from "vue";
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
import { useActionUI } from "~/composables/useActionUI";
import type { TableAction } from "~/types/table";

interface Props {
  action: TableAction;
  size?: "default" | "sm" | "lg" | "icon";
  record?: Record<string, any>;
}

const props = withDefaults(defineProps<Props>(), {
  size: "sm",
  inertia: {
    preserveScroll: true,
    preserveState: true,
    only: [],
  }
});
const emit = defineEmits<{
  (e: "click", record?: Record<string, any>): void;
  (e: "open"): void;
  (e: "close"): void;
  (e: "submit", record: Record<string, any>): void;
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
  actionName: '',
});
// verifica se o action tem inertia
const inertia = computed(() => {
  return props.action.inertia;
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

// Usa composable para UI
const { variant, size: computedSize, iconComponent, iconClasses } = useActionUI({
  action: props.action,
  defaultSize: 'sm'
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

// Ícone padrão de question para o modal
const questionIcon = computed(() => {
  const QuestionIcon = (LucideIcons as any)["CircleHelp"];
  return h(QuestionIcon);
});

// Watch para emitir eventos quando o dialog abre/fecha
watch(isOpen, (newValue) => {
  if (newValue) {
    emit("open");
  } else {
    emit("close");
  }
});

// Confirma a ação
const confirmAction = () => {
  // Verifica confirmação por digitação se necessário
  if (requiresTypedConfirmation.value && !isTypedWordCorrect.value) {
    showTypedError.value = true;
    return;
  }

  // Atualiza as propriedades do form (useForm expõe actionType/actionName como keys reativas)
  form.actionType = props.action.actionType || '';
  form.actionName = props.action.name || '';

  // Inclui o record no payload via transform (data() só envia keys de defaults; record é dinâmico)
  form.transform((data) => ({
    ...data,
    ...(props.record || {}),
  }));

  // Submit usando useForm do Inertia - processing e errors são gerenciados automaticamente
  form.submit(
    props.action.method.toLowerCase() as "post" | "put" | "patch" | "delete",
    props.action.url,
    {
      preserveScroll: inertia.value?.preserveScroll || true,
      preserveState: inertia.value?.preserveState || true,
      only: inertia.value?.only || [],
      onSuccess: (page) => {
        emit("submit", props.record ?? {});
        emit("success", page);
        isOpen.value = false;
        // Reseta palavra digitada
        typedWord.value = '';
        showTypedError.value = false;

        // Emite evento de click para compatibilidade
        emit("click", props.record);
      },
      onError: (errors) => {
        emit("error", errors);
      }
    }
  );
};

// Expõe métodos para controle externo (paridade com ActionModalSlideover)
const openDialog = () => {
  isOpen.value = true;
};
const closeDialog = () => {
  isOpen.value = false;
  typedWord.value = '';
  showTypedError.value = false;
};

defineExpose({
  open: openDialog,
  close: closeDialog,
  isOpen,
});
</script>
