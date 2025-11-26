<script setup lang="ts">  
import ResourceLayout from '~/layouts/ResourceLayout.vue';
import { type BackendBreadcrumb } from '@/composables/useBreadcrumbs';
import { ref, computed } from 'vue';
import * as LucideIcons from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Search, Copy, Check } from 'lucide-vue-next';  
import { toast } from 'vue-sonner';
import { 
  Select, 
  SelectContent, 
  SelectItem, 
  SelectTrigger, 
  SelectValue 
} from '@/components/ui/select';

interface Props {
    message?: string;
    resourceName?: string;
    resourcePluralName?: string;
    resourceLabel?: string;
    resourcePluralLabel?: string;
    maxWidth?: string;
    breadcrumbs?: BackendBreadcrumb[];
}

const props = defineProps<Props>();
 

// Busca
const searchQuery = ref('');
const selectedCategory = ref<string>('all');
const copiedIcon = ref<string | null>(null);
const itemsPerPage = 100;
const currentPage = ref(1);

// Categorias de ícones (baseado em palavras-chave comuns)
const categories = [
  { value: 'all', label: 'Todos' },
  { value: 'arrow', label: 'Setas' },
  { value: 'file', label: 'Arquivos' },
  { value: 'user', label: 'Usuários' },
  { value: 'settings', label: 'Configurações' },
  { value: 'media', label: 'Mídia' },
  { value: 'communication', label: 'Comunicação' },
  { value: 'navigation', label: 'Navegação' },
  { value: 'editor', label: 'Editor' },
  { value: 'chart', label: 'Gráficos' },
  { value: 'weather', label: 'Clima' },
  { value: 'device', label: 'Dispositivos' },
  { value: 'social', label: 'Social' },
  { value: 'commerce', label: 'Comércio' },
  { value: 'alert', label: 'Alertas' },
];

// Mapeamento de palavras-chave por categoria
const categoryKeywords: Record<string, string[]> = {
  arrow: ['arrow', 'chevron', 'move', 'corner', 'trending'],
  file: ['file', 'folder', 'document', 'page', 'clipboard', 'archive'],
  user: ['user', 'users', 'person', 'profile', 'account', 'contact'],
  settings: ['settings', 'tool', 'wrench', 'gear', 'cog', 'sliders', 'toggle'],
  media: ['play', 'pause', 'video', 'music', 'camera', 'image', 'mic', 'volume', 'speaker'],
  communication: ['mail', 'message', 'chat', 'phone', 'bell', 'notification', 'send'],
  navigation: ['home', 'menu', 'search', 'compass', 'map', 'navigation', 'route'],
  editor: ['bold', 'italic', 'underline', 'align', 'list', 'type', 'text', 'font', 'quote'],
  chart: ['chart', 'graph', 'bar', 'pie', 'line', 'trending', 'activity'],
  weather: ['sun', 'moon', 'cloud', 'rain', 'snow', 'wind', 'droplet'],
  device: ['phone', 'tablet', 'laptop', 'monitor', 'watch', 'smartphone', 'desktop', 'computer'],
  social: ['share', 'like', 'heart', 'star', 'bookmark', 'thumbs', 'eye'],
  commerce: ['shopping', 'cart', 'bag', 'credit', 'dollar', 'tag', 'gift', 'wallet'],
  alert: ['alert', 'warning', 'info', 'error', 'help', 'question', 'check', 'x', 'circle'],
};

// Função para categorizar um ícone
const getIconCategory = (iconName: string): string[] => {
  const nameLower = iconName.toLowerCase();
  const matchedCategories: string[] = [];
  
  Object.entries(categoryKeywords).forEach(([category, keywords]) => {
    if (keywords.some(keyword => nameLower.includes(keyword))) {
      matchedCategories.push(category);
    }
  });
  
  return matchedCategories;
};

// Lista de todos os ícones
const allIcons = computed(() => {
  const icons: Array<{ name: string; component: any }> = [];
  
  // Lista de chaves que não são componentes de ícones
  const excludeKeys = [
    'default',
    'createLucideIcon',
    'icons',
    'Icon',
    'createElement',
    'toKebabCase',
    'mergeClasses',
  ];
  
  Object.keys(LucideIcons).forEach((key) => {
    // Ignora exports que não são componentes de ícones
    if (excludeKeys.includes(key)) {
      return;
    }
    
    const component = (LucideIcons as any)[key]; 
    
    // Verifica se é um componente válido (tem a propriedade name ou é uma função)
    if (typeof component === 'function' || (component && typeof component === 'object')) {
      icons.push({
        name: key,
        component: component
      });
    }
  });
  
  return icons.sort((a, b) => a.name.localeCompare(b.name));
});

// Ícones filtrados por busca e categoria
const filteredIcons = computed(() => {
  let icons = allIcons.value;
  
  // Filtrar por busca
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    icons = icons.filter(icon => 
      icon.name.toLowerCase().includes(query)
    );
  }
  
  // Filtrar por categoria
  if (selectedCategory.value !== 'all') {
    icons = icons.filter((icon) => {
      const categories = getIconCategory(icon.name);
      return categories.includes(selectedCategory.value);
    });
  }
  
  return icons;
});

// Ícones paginados (apenas os que devem ser exibidos)
const paginatedIcons = computed(() => {
  const maxItems = currentPage.value * itemsPerPage;
  return filteredIcons.value.slice(0, maxItems);
});

// Verifica se tem mais ícones para carregar
const hasMore = computed(() => {
  return paginatedIcons.value.length < filteredIcons.value.length;
});

// Carregar mais ícones
const loadMore = () => {
  currentPage.value++;
};

// Resetar página quando buscar ou mudar categoria
const handleSearch = () => {
  currentPage.value = 1;
};

const handleCategoryChange = () => {
  currentPage.value = 1;
};

// Copiar nome do ícone
const copyIconName = (iconName: string) => {
  navigator.clipboard.writeText(iconName);
  copiedIcon.value = iconName;
  
  toast({
    title: 'Copiado!',
    description: `${iconName} copiado para a área de transferência`,
  });
  
  setTimeout(() => {
    copiedIcon.value = null;
  }, 2000);
};
</script>

<template>
  <ResourceLayout v-bind="props" title="Icons"> 
    <template #content>
      <div class="space-y-6 p-6">
        <!-- Header -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold tracking-tight">Ícones Lucide</h1>
              <p class="text-muted-foreground mt-2">
                Biblioteca completa de ícones Lucide Vue. Total: 
                <Badge variant="secondary" class="ml-1">{{ filteredIcons.length }}</Badge>
              </p>
            </div>
          </div>
          
          <!-- Filtros -->
          <div class="flex flex-col sm:flex-row gap-4">
            <!-- Busca -->
            <div class="relative flex-1 max-w-md">
              <Input 
                v-model="searchQuery" 
                @input="handleSearch"
                placeholder="Buscar ícones..." 
                class="w-full h-10 pr-10"
              />
              <Search class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground h-4 w-4" />
            </div>
            
            <!-- Filtro por Categoria -->
            <Select v-model="selectedCategory" @update:modelValue="handleCategoryChange">
              <SelectTrigger class="w-full sm:w-[200px]">
                <SelectValue placeholder="Categoria" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="category in categories" 
                  :key="category.value" 
                  :value="category.value"
                >
                  {{ category.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <!-- Info de resultados -->
          <div class="text-sm text-muted-foreground">
            Mostrando {{ paginatedIcons.length }} de {{ filteredIcons.length }} ícones
            <span v-if="searchQuery || selectedCategory !== 'all'">(filtrados de {{ allIcons.length }} total)</span>
          </div>
        </div>

        <!-- Grid de Ícones -->
        <div 
          v-if="paginatedIcons.length > 0"
          class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3"
        >
          <Card 
            v-for="icon in paginatedIcons" 
            :key="icon.name"
            class="group hover:border-primary hover:shadow-md transition-all cursor-pointer"
            @click="copyIconName(icon.name)"
          >
            <CardContent class="p-4 flex flex-col items-center justify-center gap-3 h-full">
              <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-muted group-hover:bg-primary/10 transition-colors">
                <component 
                  :is="icon.component" 
                  class="h-6 w-6 text-foreground group-hover:text-primary transition-colors"
                />
              </div>
              
              <div class="text-center w-full">
                <p class="text-xs font-medium truncate" :title="icon.name">
                  {{ icon.name }}
                </p>
              </div>
              
              <!-- Indicador de copiado -->
              <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <Check 
                  v-if="copiedIcon === icon.name" 
                  class="h-4 w-4 text-green-500"
                />
                <Copy 
                  v-else
                  class="h-4 w-4 text-muted-foreground"
                />
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Botão Carregar Mais -->
        <div v-if="hasMore" class="flex justify-center pt-6">
          <Button @click="loadMore" variant="outline" size="lg">
            Carregar mais {{ Math.min(itemsPerPage, filteredIcons.length - paginatedIcons.length) }} ícones
          </Button>
        </div>

        <!-- Mensagem de nenhum resultado -->
        <div 
          v-else-if="searchQuery && paginatedIcons.length === 0"
          class="flex flex-col items-center justify-center py-16 text-center"
        >
          <Search class="h-12 w-12 text-muted-foreground mb-4" />
          <h3 class="text-lg font-semibold">Nenhum ícone encontrado</h3>
          <p class="text-sm text-muted-foreground mt-2">
            Tente buscar com outro termo
          </p>
        </div>
      </div>
    </template>
  </ResourceLayout>
</template>

<style scoped>
.group:hover {
  transform: translateY(-2px);
}
</style>
