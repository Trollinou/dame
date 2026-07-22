/**
 * Utilitaire d'exportation de données au format CSV avec encodage UTF-8 (BOM inclus pour Excel).
 */

export interface ExportColumn<T> {
  header: string;
  accessor: (row: T) => string | number | boolean | null | undefined;
}

/**
 * Exporte un tableau d'objets au format CSV et déclenche le téléchargement.
 *
 * @param filename Nom du fichier (ex: 'adherents.csv')
 * @param columns Définition des colonnes à exporter
 * @param data Données à exporter
 */
export function exportToCsv<T>(
  filename: string,
  columns: ExportColumn<T>[],
  data: T[]
): void {
  if (!data || data.length === 0) return;

  // 1. En-têtes CSV
  const headers = columns.map(col => `"${escapeQuotes(col.header)}"`).join(';');

  // 2. Lignes de données
  const rows = data.map(row => {
    return columns
      .map(col => {
        const val = col.accessor(row);
        if (val === null || val === undefined) return '""';
        return `"${escapeQuotes(String(val))}"`;
      })
      .join(';');
  });

  // 3. Assemblage avec BOM UTF-8 (\uFEFF) pour compatibilité Excel
  const csvContent = '\uFEFF' + [headers, ...rows].join('\r\n');

  // 4. Création du Blob et déclenchement du téléchargement
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', filename.endsWith('.csv') ? filename : `${filename}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function escapeQuotes(val: string): string {
  return val.replace(/"/g, '""');
}
