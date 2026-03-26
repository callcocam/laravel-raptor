<!--
 * ActionButtonLink - Componente de ação que renderiza um Link do Inertia com aparência de botão
 *
 * Usado para ações que navegam usando Inertia Link (GET)
 * Mantém aparência de botão (estilo base plannerate) mas usa componente Link do Inertia
 -->
<template>
  <Link
    :href="getUrl()"
    :class="isActionStyle ? cn(actionStyle.buttonClasses, className) : cn(buttonVariants({ variant: computedVariant, size: computedSize }), 'gap-1.5', className)"
    :preserve-state="action.inertia?.preserveState ?? true"
    :preserve-scroll="action.inertia?.preserveScroll ?? true"
    :only="action.inertia?.only ?? []"
    @click="handleClick"
  >
    <div v-if="iconComponent && isActionStyle" :class="actionStyle.iconWrapperClasses">
      <component :is="iconComponent" :class="actionStyle.iconClasses" />
    </div>
    <ActionIconBox v-else-if="iconComponent" :variant="iconBoxVariant">
      <component :is="iconComponent" />
    </ActionIconBox>
    <span :class="isActionStyle ? actionStyle.labelClasses : 'text-xs'">{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import ActionIconBox from "~/components/ui/ActionIconBox.vue";
import { buttonVariants } from "~/components/ui/button";
import { cn } from "@/lib/utils";
import { useActionUI } from "~/composables/useActionUI";
import type { TableAction } from "~/types/table";

interface Props {
  action: TableAction;
  variant?: 'default' | 'create' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link' | 'success' | 'warning';
  size?: 'default' | 'sm' | 'lg' | 'icon';
  asChild?: boolean;
  className?: string;
}

const props = withDefaults(defineProps<Props>(), {
  size: "sm",
  asChild: false
});

const emit = defineEmits<{
  (e: "click"): void;
}>();

// Usa composable para variant, size e iconComponent
const { variant: computedVariant, size: computedSize, iconComponent, isActionStyle, actionStyle, iconBoxVariant } = useActionUI({
  action: props.action,
  defaultSize: 'sm',
  defaultVariant: props.variant
});

// Handler de click
const handleClick = () => {
  emit("click");
};

const getUrl = () => {
  return props.action.url || '#'
};
</script>
