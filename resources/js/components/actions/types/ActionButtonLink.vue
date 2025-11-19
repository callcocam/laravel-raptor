<!--
 * ActionButtonLink - Componente de ação que renderiza um Link do Inertia com aparência de botão
 *
 * Usado para ações que navegam usando Inertia Link (GET)
 * Mantém aparência de botão mas usa componente Link do Inertia
 -->
<template>
  <Link
    :href="action.url"
    :class="buttonClasses"
    :preserve-state="action.inertia?.preserveState ?? true"
    :preserve-scroll="action.inertia?.preserveScroll ?? true"
    :only="action.inertia?.only ?? []"
    @click="handleClick"
  >
    <component v-if="iconComponent" :is="iconComponent" class="h-3 w-3" />
    <span class="text-xs">{{ action.label }}</span>
  </Link>
</template>

<script setup lang="ts">
import { h, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { buttonVariants } from "@/components/ui/button";
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
}>();

// Mapeia cor para variant do shadcn
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

// Classes do botão usando buttonVariants
const buttonClasses = computed(() => {
  return buttonVariants({
    variant: variant.value,
    size: props.size,
    class: "gap-1 h-7 px-2.5",
  });
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

// Handler de click
const handleClick = () => {
  emit("click");
};
</script>
