<?php
/**
 * Builds the allowlisted MU payload ZIP for a future GitHub release.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

// A release asset updates only the payload; the stable MU loader is installed separately.
$project_root = dirname( __DIR__ );
$payload_root = $project_root . '/wp-content/mu-plugins/we-rock-wp-safety-lite';
$manifest     = json_decode( (string) file_get_contents( $payload_root . '/release.json' ), true );
$config       = (string) file_get_contents( $payload_root . '/src/Config.php' );
$version      = is_array( $manifest ) ? ( $manifest['version'] ?? '' ) : '';

if (
	! is_string( $version )
	|| ! preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $version )
	|| ! preg_match( "/public const VERSION = '" . preg_quote( $version, '/' ) . "';/", $config )
) {
	fwrite( STDERR, "The payload manifest and Config::VERSION must match.\n" );
	exit( 1 );
}

$output = $argv[1] ?? $project_root . '/dist/we-rock-wp-safety-lite.zip';
$parent = dirname( $output );

if ( ! is_dir( $parent ) && ! mkdir( $parent, 0755, true ) ) {
	fwrite( STDERR, "Could not create the release output directory.\n" );
	exit( 1 );
}

// Package only reviewed runtime files and the license; never include site exports or governance files.
$files = array(
	'bootstrap.php' => $payload_root . '/bootstrap.php',
	'release.json'  => $payload_root . '/release.json',
	'LICENSE'       => $project_root . '/LICENSE',
);

$source_files = glob( $payload_root . '/src/*.php' );
foreach ( is_array( $source_files ) ? $source_files : array() as $source ) {
	$files[ 'src/' . basename( $source ) ] = $source;
}

ksort( $files );
$temporary = tempnam( $parent, '.we-rock-release-' );
if ( false === $temporary ) {
	fwrite( STDERR, "Could not create a temporary release package.\n" );
	exit( 1 );
}

$zip = new ZipArchive();

if ( true !== $zip->open( $temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	unlink( $temporary );
	fwrite( STDERR, "Could not open the release ZIP.\n" );
	exit( 1 );
}

foreach ( $files as $name => $source ) {
	if ( ! is_file( $source ) || is_link( $source ) || ! $zip->addFile( $source, 'we-rock-wp-safety-lite/' . $name ) ) {
		$zip->close();
		unlink( $temporary );
		fwrite( STDERR, "Could not add a required release file.\n" );
		exit( 1 );
	}
}

if ( ! $zip->close() ) {
	unlink( $temporary );
	fwrite( STDERR, "Could not finalize the release ZIP.\n" );
	exit( 1 );
}

if ( ! rename( $temporary, $output ) ) {
	unlink( $temporary );
	fwrite( STDERR, "Could not place the completed release ZIP.\n" );
	exit( 1 );
}

echo 'Version: ' . $version . PHP_EOL;
echo 'Package: ' . $output . PHP_EOL;
echo 'SHA-256: ' . hash_file( 'sha256', $output ) . PHP_EOL;
