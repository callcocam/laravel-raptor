<template>
  <component :is="iconComponent" :class="iconClass" />
</template>

<script setup lang="ts">
import { computed, defineProps } from 'vue';
import * as LucideIcons from 'lucide-vue-next';

interface Props {
  is: string;
  class?: string;
  size?: string | number;
}

const props = withDefaults(defineProps<Props>(), {
  class: 'h-4 w-4',
  size: '16'
});

// Converter nome do ícone para o formato correto do Lucide
const normalizeIconName = (iconName: string): string => {
  // Converter kebab-case para PascalCase
  return iconName
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join('');
};

const iconComponent = computed(() => {
  // Normalizar o nome do ícone
  const normalizedName = normalizeIconName(props.is);
  
  // Tentar encontrar o ícone
  const IconComponent = (LucideIcons as any)[normalizedName];
  
  if (IconComponent) {
    return IconComponent;
  }
  
  // Fallback para HelpCircle se o ícone não for encontrado
  return (LucideIcons as any).HelpCircle || 'div';
});

const iconClass = computed(() => {
  if (props.size) {
    // Se size foi fornecido, usar ele para width e height
    const sizeValue = typeof props.size === 'number' ? `${props.size}px` : props.size;
    return `${props.class}`.replace(/h-\d+|w-\d+/g, '').trim() + ` w-[${sizeValue}] h-[${sizeValue}]`;
  }
  return props.class;
});
</script>
