<!--
 * ActionButtonLink - Componente de ação que renderiza um Link do Inertia com aparência de botão
 *
 * Usado para ações que navegam usando Inertia Link (GET)
 * Mantém aparência de botão mas usa componente Link do Inertia
 -->
<template>
  <Link
    :href="getUrl()"
    :class="cn(buttonVariants({ variant: computedVariant, size: computedSize }), 'gap-1.5 btn-gradient', className)"
    :preserve-state="action.inertia?.preserveState ?? true"
    :preserve-scroll="action.inertia?.preserveScroll ?? true"
    :only="action.inertia?.only ?? []"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" :class="iconClasses" />
    <span class="text-xs">{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { useActionUI } from "~/composables/useActionUI";
import type { TableAction } from "~/types/table";

interface Props {
  action: TableAction;
  variant?: 'default' | 'outline' | 'ghost' | 'destructive' | 'secondary' | 'link';
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
const { variant: computedVariant, size: computedSize, iconComponent, iconClasses } = useActionUI({
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
