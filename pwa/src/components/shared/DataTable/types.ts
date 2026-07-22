import type { ColumnDef } from '@tanstack/vue-table';

export interface SelectFilterOption {
  label: string;
  value: string | number;
}

export interface DataTableFilterConfig {
  id: string;
  label: string;
  options: SelectFilterOption[];
  defaultValue?: string | number;
}

export interface DataTableExportConfig<TData> {
  filename: string;
  columns: Array<{
    header: string;
    accessor: (row: TData) => string | number | boolean | null | undefined;
  }>;
}

export type CustomColumnDef<TData, TValue = unknown> = ColumnDef<TData, TValue> & {
  /** Label court à afficher dans le sélecteur de colonnes ou l'en-tête */
  headerLabel?: string;
  /** Indique si la colonne est masquable */
  enableHiding?: boolean;
};
