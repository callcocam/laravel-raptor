<!--
 * ActionButtonLink - Componente de ação que renderiza um Link do Inertia com aparência de botão
 *
 * Usado para ações que navegam usando Inertia Link (GET)
 * Mantém aparência de botão mas usa componente Link do Inertia
 -->
<template>
  <Link
    :href="getUrl()"
    :class="cn(buttonVariants({ variant: computedVariant, size: computedSize }), 'gap-1.5', className)"
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
import { h, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { buttonVariants } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import * as LucideIcons from "lucide-vue-next";
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

// Variant computado - usa prop ou mapeia da cor
const computedVariant = computed(() => {
  if (props.variant) return props.variant;
  
  const colorMap: Record<string, any> = {
    green: "default",
    blue: "default",
    red: "destructive",
    yellow: "outline",
    gray: "secondary",
    default: "default",
  };

  return colorMap[props.action.color || "default"] || "default";
});

// Size computado
const computedSize = computed(() => props.size);

// Classes do ícone
const iconClasses = computed(() => {
  const sizeMap: Record<string, string> = {
    'sm': 'h-3 w-3',
    'default': 'h-3.5 w-3.5',
    'lg': 'h-4 w-4',
    'icon': 'h-4 w-4'
  };
  return sizeMap[props.size] || 'h-3 w-3';
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

const getUrl = () => {
  return props.action.url || '#'
};
</script>
