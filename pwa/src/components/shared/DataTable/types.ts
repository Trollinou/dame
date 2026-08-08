import type { ColumnDef } from '@tanstack/vue-table';
import type { ExportColumn } from '@/utils/csvExport';

export type { ExportColumn };

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
  columns: ExportColumn<TData>[];
}

export type CustomColumnDef<TData, TValue = unknown> = ColumnDef<TData, TValue> & {
  /** Label court à afficher dans le sélecteur de colonnes ou l'en-tête */
  headerLabel?: string;
  /** Indique si la colonne est masquable */
  enableHiding?: boolean;
};
