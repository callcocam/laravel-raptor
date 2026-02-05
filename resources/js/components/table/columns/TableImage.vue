<template>
    <div class="inline-flex items-center" :title="column.tooltip">
        <button
            v-if="imageUrl && column.clickable"
            type="button"
            :class="[
                'rounded-full border border-border transition-all hover:ring-2 hover:ring-ring hover:ring-offset-2',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                'cursor-pointer'
            ]"
            @click="openModal"
        >
            <img
                :src="imageUrl || column.defaultImage"
                :alt="altText || 'Image'"
                :class="[
                    'object-contain',
                    roundedClasses,
                    sizeClasses
                ]"
                @error="handleImageError"
            />
        </button>
        <img
            v-else-if="imageUrl"
            :src="imageUrl || column.defaultImage"
            :alt="altText || 'Image'"
            :class="[
                'object-contain border border-border',
                roundedClasses,
                sizeClasses
            ]"
            @error="handleImageError"
        />
        <div
            v-else
            :class="[
                'flex items-center justify-center bg-muted text-muted-foreground',
                roundedClasses,
                sizeClasses
            ]"
        >
            <Icon :is="fallbackIcon" :class="iconSizeClasses" />
        </div>
    </div>

    <!-- Modal para visualizar imagem em tamanho maior -->
    <Dialog v-model:open="isModalOpen">
        <DialogContent class="max-w-4xl">
            <DialogHeader>
                <DialogTitle>{{ altText || 'Image' }}</DialogTitle>
            </DialogHeader>
            <div class="flex items-center justify-center p-4">
                <img
                    :src="imageUrl || column.defaultImage"
                    :alt="altText || 'Image'"
                    class="max-h-[70vh] w-auto object-contain rounded-lg"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
<script lang="ts" setup>
import { computed, ref } from 'vue'
import Icon from '~/components/icon.vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

const props = defineProps<{
    record: Record<string, any>
    column: {
        name: string
        tooltip?: string
        size?: 'sm' | 'md' | 'lg' | 'xl'
        altKey?: string
        fallbackIcon?: string
        rounded?: boolean
        clickable?: boolean
        objectFit?: 'cover' | 'contain' | 'fill'
        [key: string]: any
    }
}>()

const imageError = ref(false)
const isModalOpen = ref(false)

/**
 * Obtém a URL da imagem, suportando acesso aninhado
 */
const imageUrl = computed(() => {
    if (imageError.value) return null

    const keys = props.column.name.split('.')
    let result = props.record

    for (const key of keys) {
        if (result && typeof result === 'object' && key in result) {
            result = result[key]
        } else {
            return null
        }
    }

    return result || null
})

/**
 * Obtém o texto alternativo da imagem
 */
const altText = computed<any>(() => {
    if (props.column.altKey) {
        const keys = props.column.altKey.split('.')
        let result = props.record

        for (const key of keys) {
            if (result && typeof result === 'object' && key in result) {
                result = result[key]
            } else {
                return 'Image'
            }
        }

        return result || 'Image'
    }

    return 'Image'
})

/**
 * Define o ícone de fallback quando a imagem não carrega
 */
const fallbackIcon = computed<any>(() => {
    return props.column.fallbackIcon || 'ImageOff'
})

/**
 * Define as classes de arredondamento
 */
const roundedClasses = computed<any>(() => {
    return props.column.rounded ? 'rounded-full' : 'rounded-md'
})

/**
 * Define as classes de tamanho da imagem
 */
const sizeClasses = computed<any>(() => {
    const size = props.column.size || 'md'

    const sizes = {
        sm: 'h-8 w-8',
        md: 'h-12 w-12',
        lg: 'h-16 w-16',
        xl: 'h-24 w-24'
    }

    return sizes[size]
})

/**
 * Define as classes de tamanho do ícone de fallback
 */
const iconSizeClasses = computed<any>(() => {
    const size = props.column.size || 'md'

    const sizes = {
        sm: 'h-4 w-4',
        md: 'h-6 w-6',
        lg: 'h-8 w-8',
        xl: 'h-12 w-12'
    }

    return sizes[size]
})

/**
 * Handler para erro no carregamento da imagem
 */
const handleImageError = () => {
    imageError.value = true
}

/**
 * Abre o modal com a imagem em tamanho maior
 */
const openModal = () => {
    if (props.column.clickable && imageUrl.value) {
        isModalOpen.value = true
    }
}
</script>