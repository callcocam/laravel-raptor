import { computed } from "vue";

interface FormColumn {
  name: string;
  columnSpan?: string;
  order?: number;
  responsive?: {
    span?: { sm?: string; md?: string; lg?: string; xl?: string };
  };
  [key: string]: any;
}

interface GridLayoutOptions {
  gridColumns?: string;
  gap?: string;
}

/**
 * Composable para gerar classes de grid layout responsivo
 * Usado em FormRenderer e outros componentes que precisam de grid layout
 */
export function useGridLayout(options: GridLayoutOptions = {}) {
  const gridColsMap: Record<string, string> = {
    "1": "md:grid-cols-1",
    "2": "md:grid-cols-2",
    "3": "md:grid-cols-3",
    "4": "md:grid-cols-4",
    "5": "md:grid-cols-5",
    "6": "md:grid-cols-6",
    "7": "md:grid-cols-7",
    "8": "md:grid-cols-8",
    "9": "md:grid-cols-9",
    "10": "md:grid-cols-10",
    "11": "md:grid-cols-11",
    "12": "md:grid-cols-12",
  };

  const gapMap: Record<string, string> = {
    "0": "gap-x-0",
    "1": "gap-x-1",
    "2": "gap-x-2",
    "3": "gap-x-3",
    "4": "gap-x-4",
    "5": "gap-x-5",
    "6": "gap-x-6",
    "8": "gap-x-8",
  };

  const colSpanMap: Record<string, string> = {
    "1": "md:col-span-1",
    "2": "md:col-span-2",
    "3": "md:col-span-3",
    "4": "md:col-span-4",
    "5": "md:col-span-5",
    "6": "md:col-span-6",
    "7": "md:col-span-7",
    "8": "md:col-span-8",
    "9": "md:col-span-9",
    "10": "md:col-span-10",
    "11": "md:col-span-11",
    "12": "md:col-span-12",
    full: "md:col-span-full",
  };

  /**
   * Gera classes do grid container
   */
  const getFormClasses = (gridColumns = "12", gap = "4") => {
    return [
      "grid",
      "grid-cols-1",
      gridColsMap[gridColumns] || "md:grid-cols-12",
      gapMap[gap] || "gap-x-4",
      "gap-y-4", // Espaçamento vertical entre campos
    ].join(" ");
  };

  /**
   * Gera classes do grid para cada coluna
   */
  const getColumnClasses = (column: FormColumn) => {
    const classes: string[] = [];

    // Mobile: sempre full width (col-span-1 dentro de grid-cols-1)
    classes.push("col-span-1");

    // Column span padrão (desktop - md:)
    if (column.columnSpan) {
      const spanClass = colSpanMap[column.columnSpan];
      if (spanClass) {
        classes.push(spanClass);
      }
    }

    // Column span responsivo
    if (column.responsive?.span) {
      const { sm, md, lg, xl } = column.responsive.span;
      if (sm && colSpanMap[sm])
        classes.push(colSpanMap[sm].replace("md:", "sm:"));
      if (md && colSpanMap[md]) classes.push(colSpanMap[md]);
      if (lg && colSpanMap[lg])
        classes.push(colSpanMap[lg].replace("md:", "lg:"));
      if (xl && colSpanMap[xl])
        classes.push(colSpanMap[xl].replace("md:", "xl:"));
    }

    return classes.join(" ");
  };

  /**
   * Gera estilos inline para ordem (se especificado)
   */
  const getColumnStyles = (column: FormColumn) => {
    if (column.order !== undefined) {
      return { order: column.order };
    }
    return {};
  };

  /**
   * Gera classes do grid container como computed
   */
  const formClasses = computed(() => {
    return getFormClasses(options.gridColumns, options.gap);
  });

  return {
    formClasses,
    getFormClasses,
    getColumnClasses,
    getColumnStyles,
    gridColsMap,
    gapMap,
    colSpanMap,
  };
}
