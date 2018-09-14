<?php
/**
 * @package Redbit\SimpleShop\WpPlugin
 * @license MIT
 * @copyright 2016-2018 Redbit s.r.o.
 * @author Redbit s.r.o. <info@redbit.cz>
 */

$deploy          = new DeployScript( 'simpleshop-cz' );
$deploy->distDir = __DIR__ . '/dist';

try {
	$deploy->run( $argv );
} catch ( \Exception $e ) {
	$message = $e->getMessage();
	echo PHP_EOL;
	fwrite( STDERR, 'Error: ' . $message . PHP_EOL );
	die( 1 );
}



class DeployScript {
	/** @var string */
	public $distDir = '';

	/** @var string */
	private $productName;

	/** @var string */
	private $scriptName;

	/** @var string|null */
	private $version;

	/**
	 * @param string $productName
	 */
	public function __construct( $productName ) {
		$this->productName = $productName;
	}


	/**
	 * @param array $args
	 *
	 * @throws RuntimeException
	 */
	public function run( array $args ) {
		$this->scriptName = $args[0];
		$this->version    = isset( $args[1] ) ? $args[1] : null;

		$this->validateVersion( $this->version );

		// Building package
		echo sprintf( 'Building package for version %s... ', $this->version );
		$package = $this->buildPackage();
		echo sprintf( "OK\nBuilt package at %s\n", $package );
	}

	/**
	 * @param string|null $version
	 *
	 * @throws RuntimeException
	 */
	private function validateVersion( $version ) {
		if ( $version === null ) {
			echo sprintf( "Usage: php %1\$s <version>\nExample: php %1\$s v1.2.3\n", $this->scriptName );
			die( 0 );
		}

		if ( ! preg_match( '/^v\d+\.\d+\.\d+(-[-.a-z0-9]+)?$/D', $version ) ) {
			throw new \RuntimeException( sprintf(
				"Argument \"version\" value \"%s\" has invalid format.\n" .
				"Example stable: v1.2.3\nExample unstable: v1.2.3-beta",
				$version
			) );
		}
	}

	/**
	 * @return string
	 * @throws RuntimeException
	 */
	private function buildPackage() {
		$this->prepareDist();

		$packageFile = $this->getPackageName();
		$this->zip( __DIR__, $packageFile );

		$stampedFile = $this->copyStampedFile(
			__DIR__ . '/' . $this->productName . '.php',
			array(
				'Version: dev-master'                            => sprintf( 'Version: %s', $this->getNakedVersion() ),
				'define( \'SIMPLESHOP_PLUGIN_VERSION\', \'dev-master\' );' => sprintf( 'define( \'SIMPLESHOP_PLUGIN_VERSION\', \'%s\' );',
					$this->version ),
			),
			$this->distDir
		);

		$this->zip( $stampedFile, $packageFile, $this->distDir );
		unlink( $stampedFile );

		return $packageFile;
	}

	/**
	 * @throws RuntimeException
	 */
	private function prepareDist() {
		if ( in_array( $this->distDir, array( null, '', __DIR__ ), true ) ) {
			return;
		}

		$this->createDir( $this->distDir );
		$this->cleanDir( $this->distDir );
	}

	/**
	 * @param string $dir
	 *
	 * @throws RuntimeException
	 */
	private function createDir( $dir ) {
		if ( ! is_dir( $dir ) && ! mkdir( $dir ) && ! is_dir( $dir ) ) {
			throw new \RuntimeException( sprintf( 'Directory "%1$s" was not created', $dir ) );
		}
	}

	/**
	 * @param string $dir
	 *
	 * @throws RuntimeException
	 */
	private function cleanDir( $dir ) {
		$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator(
			$dir,
			FilesystemIterator::SKIP_DOTS
		) );

		/** @var SplFileInfo $file */
		foreach ( $files as $file ) {
			if ( $file->isDir() ) {
				continue;
			}
			if ( ! unlink( $fileName = $file->getPathname() ) ) {
				throw new \RuntimeException( sprintf( 'Unable to delete file "%s"', $fileName ) );
			}
		}
	}

	/**
	 * @return string
	 */
	private function getPackageName() {
		return sprintf(
			'%s/%s-%s.zip',
			$this->distDir,
			$this->productName,
			$this->version
		);
	}

	/**
	 * @param string $fileOrPath
	 * @param string $zip
	 * @param string $baseDir
	 *
	 * @throws RuntimeException
	 */
	private function zip( $fileOrPath, $zip, $baseDir = __DIR__ ) {
		// Short path to remove root-directory in zip (/home/user/project/data.ext -> ./data.ext)
		$relativePath = str_replace( $baseDir, '.', $fileOrPath );

		$prevDir = getcwd();

		$cmd = sprintf(
			'zip -9rq -x@%s %s %s',
			__DIR__ . '/deploy-exclude.lst',
			escapeshellarg( $zip ),
			escapeshellarg( $relativePath )
		);

		chdir( $baseDir );
		passthru( $cmd, $return_var );
		chdir( $prevDir );

		if ( $return_var !== 0 ) {
			throw new \RuntimeException( sprintf( 'Zip failed, command was: "%s"', $cmd ) );
		}
	}

	/**
	 * Strips "v" from versions (v1.2.3-beta -> 1.2.3)
	 *
	 * @return bool|string
	 */
	private function getNakedVersion() {
		return preg_replace('/^v(\d+\.\d+\.\d+)(?:-.*)+$/D', '$1', $this->version);
	}

	/**
	 * @param string $fileName Source file to copy$stamp
	 * @param array $replacements Stamp replacements for `str_replace()`, key = search pattern, value = replacement
	 * @param string $targetDir Dir path to save stamped file
	 *
	 * @return string Filename of copied file
	 * @throws RuntimeException
	 */
	private function copyStampedFile( $fileName, $replacements, $targetDir ) {
		$content = file_get_contents( $fileName );
		if ( $content === false ) {
			throw new \RuntimeException( sprintf( 'Unable to open file: "%s"', $fileName ) );
		}

		foreach ( $replacements as $from => $to ) {
			$content = str_replace( $from, $to, $content, $count );
			if ( $count === 0 ) {
				throw new \RuntimeException( sprintf( 'Not found pattern "%s" in file: %s', $from, $fileName ) );
			}
		}

		$newFilename = rtrim( $targetDir, '/' ) . '/' . basename( $fileName );
		file_put_contents( $newFilename, $content );

		return $newFilename;
	}
}