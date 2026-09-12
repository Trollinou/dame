<?php
/**
 * Document Storage Service.
 *
 * @package DAME
 */

declare(strict_types=1);

namespace DAME\Services;

/**
 * Class Document_Storage
 * Manages isolated, secure file storage for pre-inscriptions and member documents.
 */
class Document_Storage {

	/**
	 * Subfolder name under wp-content/uploads
	 */
	public const FOLDER_NAME = 'dame-documents';

	/**
	 * Retrieve the absolute path to the dame documents directory.
	 * Automatically creates the folder and protection files (.htaccess, index.php) if missing.
	 *
	 * @return string Absolute directory path with trailing slash.
	 */
	public static function get_storage_dir(): string {
		$upload_dir = wp_upload_dir();
		$base_dir   = trailingslashit( (string) $upload_dir['basedir'] ) . self::FOLDER_NAME . '/';

		if ( ! file_exists( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
			self::secure_directory( $base_dir );
		}

		return $base_dir;
	}

	/**
	 * Creates protection files in the directory to prevent direct public access.
	 *
	 * @param string $dir Directory to secure.
	 */
	public static function secure_directory( string $dir ): void {
		$htaccess = $dir . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules = "# Interdire le listing et l'accès direct aux documents DAME\n" .
					"<IfModule mod_authz_core.c>\n" .
					"    Require all denied\n" .
					"</IfModule>\n" .
					"<IfModule !mod_authz_core.c>\n" .
					"    Order deny,allow\n" .
					"    Deny from all\n" .
					"</IfModule>\n";
			file_put_contents( $htaccess, $rules );
		}

		$index = $dir . 'index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Store raw file contents safely into the documents directory.
	 *
	 * @param string $content  Binary or string content.
	 * @param string $filename Desired filename.
	 * @return string Stored filename (without directory prefix).
	 */
	public static function save_file( string $content, string $filename ): string {
		$dir       = self::get_storage_dir();
		$safe_name = wp_unique_filename( $dir, sanitize_file_name( $filename ) );
		$filepath  = $dir . $safe_name;

		file_put_contents( $filepath, $content );

		return $safe_name;
	}

	/**
	 * Save a local temporary file into the documents directory.
	 *
	 * @param string $temp_file Temporary absolute file path.
	 * @param string $filename  Desired target filename.
	 * @return string|null Stored filename on success, null on failure.
	 */
	public static function import_file( string $temp_file, string $filename ): ?string {
		if ( ! file_exists( $temp_file ) || ! is_readable( $temp_file ) ) {
			return null;
		}

		$dir       = self::get_storage_dir();
		$safe_name = wp_unique_filename( $dir, sanitize_file_name( $filename ) );
		$target    = $dir . $safe_name;

		if ( @copy( $temp_file, $target ) ) {
			return $safe_name;
		}

		return null;
	}

	/**
	 * Duplicate an existing stored document into a new file.
	 *
	 * @param string $source_filename Existing relative or absolute filename.
	 * @param string $target_filename Desired target filename.
	 * @return string|null Stored new filename on success, null on failure.
	 */
	public static function duplicate_file( string $source_filename, string $target_filename ): ?string {
		$source_path = self::get_absolute_path( $source_filename );
		if ( ! $source_path || ! file_exists( $source_path ) ) {
			return null;
		}

		return self::import_file( $source_path, $target_filename );
	}

	/**
	 * Retrieve the absolute path of a stored document.
	 *
	 * @param string $relative_or_absolute Path or filename.
	 * @return string|null Absolute path if file exists, null otherwise.
	 */
	public static function get_absolute_path( string $relative_or_absolute ): ?string {
		if ( empty( $relative_or_absolute ) ) {
			return null;
		}

		$dir = self::get_storage_dir();

		// If already an absolute path inside our storage dir
		if ( str_starts_with( $relative_or_absolute, $dir ) ) {
			return file_exists( $relative_or_absolute ) ? $relative_or_absolute : null;
		}

		// Strip potential folder prefixes
		$filename  = basename( $relative_or_absolute );
		$full_path = $dir . $filename;

		return file_exists( $full_path ) ? $full_path : null;
	}

	/**
	 * Delete a document from storage.
	 *
	 * @param string $relative_or_absolute Path or filename.
	 * @return bool True if deleted or already non-existent, false on failure.
	 */
	public static function delete_file( string $relative_or_absolute ): bool {
		$path = self::get_absolute_path( $relative_or_absolute );
		if ( ! $path ) {
			return true;
		}

		// Security: ensure path is strictly inside our storage directory
		$dir = self::get_storage_dir();
		if ( ! str_starts_with( $path, $dir ) ) {
			return false;
		}

		return @unlink( $path );
	}
}
