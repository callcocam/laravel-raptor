<script setup lang="ts">
/**
 * SelectWithClear — select nativo sem dependências externas (sem reka-ui/shadcn).
 *
 * Aceita opções nos formatos:
 *   - { id, name }       → padrão do backend Raptor (Filters/Columns)
 *   - { value, label }   → formato comum de frontend
 *   - string[]           → lista simples
 *
 * Para dados do backend, use optionValue / optionLabel para apontar
 * para as chaves corretas do objeto retornado.
 */
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { ChevronDown, X, Check, Search } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

// ── Types ────────────────────────────────────────────────────────────────────

type RawOption = string | Record<string, any>;

interface NormalizedOption {
    value: string;
    label: string;
    raw: RawOption;
}

interface Props {
    modelValue?: string | null;
    options?: RawOption[];
    placeholder?: string;
    label?: string;
    disabled?: boolean;
    searchable?: boolean;
    /** Chave do objeto usada como valor (default: 'id', fallback 'value') */
    optionValue?: string;
    /** Chave do objeto usada como label (default: 'name', fallback 'label') */
    optionLabel?: string;
    /** Classe extra para o wrapper externo */
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    options: () => [],
    placeholder: 'Selecionar…',
    disabled: false,
    searchable: false,
    optionValue: 'id',
    optionLabel: 'name',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
    (e: 'change', value: string | null): void;
}>();

// ── Estado ───────────────────────────────────────────────────────────────────

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);
const rootRef = ref<HTMLElement | null>(null);
const searchRef = ref<HTMLInputElement | null>(null);
const listRef = ref<HTMLElement | null>(null);
const triggerId = `select-trigger-${Math.random().toString(36).slice(2, 8)}`;
const listboxId = `select-listbox-${Math.random().toString(36).slice(2, 8)}`;

// ── Normalização de opções ────────────────────────────────────────────────────

const normalize = (opt: RawOption): NormalizedOption => {
    if (typeof opt === 'string') {
        return { value: opt, label: opt, raw: opt };
    }
    const value = String(opt[props.optionValue] ?? opt.value ?? opt.id ?? '');
    const label = String(opt[props.optionLabel] ?? opt.label ?? opt.name ?? value);
    return { value, label, raw: opt };
};

const normalizedOptions = computed<NormalizedOption[]>(() =>
    props.options.map(normalize),
);

// ── Filtro por pesquisa ───────────────────────────────────────────────────────

const filteredOptions = computed<NormalizedOption[]>(() => {
    if (!searchQuery.value.trim()) return normalizedOptions.value;
    const q = searchQuery.value.toLowerCase();
    return normalizedOptions.value.filter((o) => o.label.toLowerCase().includes(q));
});

// ── Valor selecionado ─────────────────────────────────────────────────────────

const hasValue = computed(() => !!props.modelValue);

const displayValue = computed(() => {
    if (!props.modelValue) return '';
    return normalizedOptions.value.find((o) => o.value === props.modelValue)?.label ?? props.modelValue;
});

// ── Abrir / fechar ────────────────────────────────────────────────────────────

const open = () => {
    if (props.disabled) return;
    isOpen.value = true;
    searchQuery.value = '';
    highlightedIndex.value = normalizedOptions.value.findIndex(
        (o) => o.value === props.modelValue,
    );
    if (highlightedIndex.value === -1) highlightedIndex.value = 0;
    nextTick(() => {
        if (props.searchable) {
            searchRef.value?.focus();
        }
        scrollHighlightedIntoView();
    });
};

const close = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

const toggle = () => {
    if (isOpen.value) {
        close();
    } else {
        open();
    }
};

// ── Selecionar / Limpar ───────────────────────────────────────────────────────

const select = (opt: NormalizedOption) => {
    emit('update:modelValue', opt.value);
    emit('change', opt.value);
    close();
};

const clear = (e: MouseEvent) => {
    e.stopPropagation();
    emit('update:modelValue', null);
    emit('change', null);
    if (isOpen.value) close();
};

// ── Teclado ───────────────────────────────────────────────────────────────────

const scrollHighlightedIntoView = () => {
    nextTick(() => {
        const list = listRef.value;
        if (!list) return;
        const item = list.querySelector<HTMLElement>('[data-highlighted="true"]');
        item?.scrollIntoView({ block: 'nearest' });
    });
};

const handleTriggerKeydown = (e: KeyboardEvent) => {
    switch (e.key) {
        case 'Enter':
        case ' ':
        case 'ArrowDown':
            e.preventDefault();
            open();
            break;
        case 'ArrowUp':
            e.preventDefault();
            open();
            break;
        case 'Escape':
            close();
            break;
    }
};

const handleListKeydown = (e: KeyboardEvent) => {
    const opts = filteredOptions.value;
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            highlightedIndex.value = Math.min(highlightedIndex.value + 1, opts.length - 1);
            scrollHighlightedIntoView();
            break;
        case 'ArrowUp':
            e.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollHighlightedIntoView();
            break;
        case 'Home':
            e.preventDefault();
            highlightedIndex.value = 0;
            scrollHighlightedIntoView();
            break;
        case 'End':
            e.preventDefault();
            highlightedIndex.value = opts.length - 1;
            scrollHighlightedIntoView();
            break;
        case 'Enter':
            e.preventDefault();
            if (opts[highlightedIndex.value]) {
                select(opts[highlightedIndex.value]);
            }
            break;
        case 'Escape':
        case 'Tab':
            close();
            break;
    }
};

// Resetar highlight ao filtrar
watch(searchQuery, () => {
    highlightedIndex.value = filteredOptions.value.length > 0 ? 0 : -1;
});

// ── Click outside ─────────────────────────────────────────────────────────────

const handleClickOutside = (e: MouseEvent) => {
    if (!rootRef.value?.contains(e.target as Node)) {
        close();
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div ref="rootRef" :class="cn('relative', props.class)">
        <!-- ── Label ──────────────────────────────────────────────────────── -->
        <label
            v-if="label"
            :for="triggerId"
            class="mb-1.5 block text-xs font-medium text-muted-foreground"
        >
            {{ label }}
        </label>

        <!-- ── Trigger ───────────────────────────────────────────────────── -->
        <button
            :id="triggerId"
            type="button"
            role="combobox"
            :aria-expanded="isOpen"
            :aria-haspopup="'listbox'"
            :aria-controls="listboxId"
            :disabled="disabled"
            :class="cn(
                'flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input',
                'bg-background px-3 text-sm shadow-xs',
                'transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                isOpen ? 'ring-2 ring-ring border-ring' : 'hover:border-ring/50',
                disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            )"
            @click="toggle"
            @keydown="handleTriggerKeydown"
        >
            <!-- Texto selecionado ou placeholder -->
            <span
                :class="cn(
                    'flex-1 truncate text-left',
                    !hasValue ? 'text-muted-foreground' : 'text-foreground',
                )"
            >
                {{ hasValue ? displayValue : placeholder }}
            </span>

            <!-- Ícone da direita: X (limpar) ou chevron -->
            <span class="flex shrink-0 items-center">
                <!-- Botão limpar -->
                <span
                    v-if="hasValue"
                    role="button"
                    tabindex="-1"
                    :aria-label="`Limpar ${label || 'seleção'}`"
                    title="Limpar"
                    class="flex size-5 items-center justify-center rounded text-muted-foreground/60 transition-colors hover:bg-accent hover:text-foreground"
                    @click="clear"
                    @mousedown.stop
                >
                    <X class="size-3.5" />
                </span>

                <!-- Chevron (sempre visível) -->
                <ChevronDown
                    :class="cn(
                        'ml-0.5 size-4 shrink-0 text-muted-foreground/60 transition-transform duration-150',
                        isOpen ? 'rotate-180' : '',
                    )"
                />
            </span>
        </button>

        <!-- ── Dropdown panel ─────────────────────────────────────────────── -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 translate-y-0.5 scale-[0.98]"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-0.5 scale-[0.98]"
        >
            <div
                v-if="isOpen"
                :id="listboxId"
                class="absolute left-0 top-[calc(100%+4px)] z-50 w-full min-w-[10rem] overflow-hidden rounded-lg border border-border bg-popover shadow-lg shadow-black/10 ring-1 ring-border/30"
                @keydown="handleListKeydown"
            >
                <!-- Campo de pesquisa -->
                <div v-if="searchable" class="border-b border-border/60 p-1.5">
                    <div class="flex items-center gap-1.5 rounded-md bg-muted/50 px-2 py-1">
                        <Search class="size-3.5 shrink-0 text-muted-foreground/60" />
                        <input
                            ref="searchRef"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Pesquisar…"
                            class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/50"
                            @keydown="handleListKeydown"
                        />
                    </div>
                </div>

                <!-- Lista de opções -->
                <div
                    ref="listRef"
                    role="listbox"
                    :aria-label="label || placeholder"
                    class="max-h-60 overflow-y-auto py-1"
                >
                    <!-- Sem resultados -->
                    <div
                        v-if="filteredOptions.length === 0"
                        class="px-3 py-4 text-center text-sm text-muted-foreground"
                    >
                        Nenhuma opção encontrada.
                    </div>

                    <!-- Opções -->
                    <button
                        v-for="(opt, idx) in filteredOptions"
                        :key="opt.value"
                        type="button"
                        role="option"
                        :aria-selected="opt.value === modelValue"
                        :data-highlighted="idx === highlightedIndex ? 'true' : undefined"
                        :class="cn(
                            'flex w-full items-center gap-2 px-3 py-1.5 text-sm transition-colors',
                            opt.value === modelValue
                                ? 'bg-primary/10 font-medium text-primary'
                                : 'text-foreground',
                            idx === highlightedIndex
                                ? 'bg-accent text-accent-foreground'
                                : 'hover:bg-accent/60 hover:text-accent-foreground',
                        )"
                        @click="select(opt)"
                        @mouseenter="highlightedIndex = idx"
                    >
                        <Check
                            :class="cn(
                                'size-3.5 shrink-0',
                                opt.value === modelValue ? 'opacity-100 text-primary' : 'opacity-0',
                            )"
                        />
                        <span class="flex-1 truncate text-left">{{ opt.label }}</span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
