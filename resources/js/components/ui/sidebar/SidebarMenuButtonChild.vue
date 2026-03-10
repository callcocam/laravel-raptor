<script lang="ts">
import { cn } from '~/lib/utils'
import { cloneVNode, defineComponent, h, mergeProps } from 'vue'
import { type SidebarMenuButtonVariants, sidebarMenuButtonVariants } from '.'

export interface SidebarMenuButtonProps {
    as?: string | object
    asChild?: boolean
    variant?: SidebarMenuButtonVariants['variant']
    size?: SidebarMenuButtonVariants['size']
    isActive?: boolean
    class?: string
}

export default defineComponent({
    name: 'SidebarMenuButtonChild',
    inheritAttrs: false,
    props: {
        as: { type: [String, Object], default: 'button' },
        asChild: { type: Boolean, default: false },
        variant: { type: String as () => SidebarMenuButtonVariants['variant'], default: 'default' },
        size: { type: String as () => SidebarMenuButtonVariants['size'], default: 'default' },
        isActive: { type: Boolean, default: false },
        class: { type: String, default: '' },
    },
    setup(props, { slots, attrs }) {
        return () => {
            const buttonClass = cn(
                sidebarMenuButtonVariants({ variant: props.variant, size: props.size }),
                props.class,
            )

            const mergedProps = mergeProps(attrs, {
                'data-slot': 'sidebar-menu-button',
                'data-sidebar': 'menu-button',
                'data-size': props.size,
                ...(props.isActive ? { 'data-active': 'true' } : {}),
                class: buttonClass,
            })

            if (props.asChild && slots.default) {
                const children = slots.default()
                const firstChild = children.find(vnode => vnode.type !== Comment && vnode.type !== Text)
                if (firstChild) {
                    return cloneVNode(firstChild, mergedProps)
                }
            }

            return h(props.as as string, mergedProps, slots.default?.())
        }
    },
})
</script>
