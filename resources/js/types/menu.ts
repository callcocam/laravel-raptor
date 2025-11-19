export interface MenuItem {
  id: string;
  label: string;
  icon: string;
  order: number;
  singleModelName?: string;
  pluralModelName?: string;
  route?: string;
  group?: string;
  class?: string;
  methods?: string[];
  children?: MenuItem[];
  type?: string;
  name?: string;
}

export interface MenuActiveStateCache {
  itemId: string;
  routeName: string | null | undefined;
  isActive: boolean;
}

export const ACTIVE_MENU_CLASSES = {
  base: 'flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 cursor-pointer',
  hover: 'hover:bg-accent/50',
  active: 'bg-accent text-accent-foreground shadow-sm',
  inactive: 'text-muted-foreground hover:text-foreground',
} as const;

export const ACTIVE_SUBMENU_CLASSES = {
  base: 'flex items-center gap-3 px-3 py-1.5 ml-6 rounded-md text-sm transition-all duration-200 cursor-pointer relative before:absolute before:left-0 before:top-0 before:bottom-0 before:w-0.5',
  hover: 'hover:bg-accent/30',
  active: 'bg-accent/50 text-foreground font-medium before:bg-primary',
  inactive: 'text-muted-foreground hover:text-foreground before:bg-transparent',
} as const;

export const ACTIVE_GROUP_CLASSES = {
  base: 'w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-all duration-200',
  hover: 'hover:bg-accent/30',
  active: 'text-foreground',
  inactive: 'text-muted-foreground hover:text-foreground',
} as const;
