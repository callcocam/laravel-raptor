<!--
 * ThemeSwitcher - Componente para troca de temas
 *
 * Permite ao usuário selecionar e aplicar diferentes configurações de tema:
 * - Cores (azul, verde, roxo, etc.)
 * - Fontes (Inter, Noto Sans, etc.)
 * - Arredondamento (pequeno, médio, grande, etc.)
 * - Variantes (padrão, mono, escalado)
 *
 * @example
 * <ThemeSwitcher />
 -->
<template>
  <div class="space-y-6">
    <!-- Seletor de Cor -->
    <div class="space-y-2">
      <label class="text-sm font-medium">Cor do Tema</label>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="themeOption in availableThemes"
          :key="themeOption.name"
          :class="cn(
            'px-4 py-2 rounded-md border text-sm font-medium transition-colors',
            theme.color === themeOption.color
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-border hover:bg-accent hover:text-accent-foreground'
          )"
          @click="setColor(themeOption.color || 'default')"
        >
          {{ themeOption.label }}
        </button>
      </div>
    </div>

    <!-- Seletor de Fonte -->
    <div class="space-y-2">
      <label class="text-sm font-medium">Fonte</label>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="font in availableFonts"
          :key="font.value"
          :class="cn(
            'px-4 py-2 rounded-md border text-sm font-medium transition-colors',
            theme.font === font.value
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-border hover:bg-accent hover:text-accent-foreground'
          )"
          @click="setFont(font.value)"
        >
          {{ font.label }}
        </button>
      </div>
    </div>

    <!-- Seletor de Arredondamento -->
    <div class="space-y-2">
      <label class="text-sm font-medium">Arredondamento</label>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="rounded in availableRounded"
          :key="rounded.value"
          :class="cn(
            'px-4 py-2 rounded-md border text-sm font-medium transition-colors',
            theme.rounded === rounded.value
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-border hover:bg-accent hover:text-accent-foreground'
          )"
          @click="setRounded(rounded.value)"
        >
          {{ rounded.label }}
        </button>
      </div>
    </div>

    <!-- Seletor de Variante -->
    <div class="space-y-2">
      <label class="text-sm font-medium">Variante</label>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="variant in availableVariants"
          :key="variant.value"
          :class="cn(
            'px-4 py-2 rounded-md border text-sm font-medium transition-colors',
            theme.variant === variant.value
              ? 'border-primary bg-primary text-primary-foreground'
              : 'border-border hover:bg-accent hover:text-accent-foreground'
          )"
          @click="setVariant(variant.value)"
        >
          {{ variant.label }}
        </button>
      </div>
    </div>

    <!-- Botão de Reset -->
    <div class="pt-4 border-t">
      <button
        class="w-full px-4 py-2 rounded-md border border-border hover:bg-accent hover:text-accent-foreground text-sm font-medium transition-colors"
        @click="resetTheme"
      >
        Restaurar Tema Padrão
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTheme } from '@/composables/useTheme'
import { cn } from '@/lib/utils'

const {
  theme,
  setColor,
  setFont,
  setRounded,
  setVariant,
  resetTheme,
  availableThemes,
  availableFonts,
  availableRounded,
  availableVariants,
} = useTheme()
</script>
