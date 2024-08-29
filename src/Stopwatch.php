<?php

declare( strict_types=1 );

namespace Redbit\SimpleShop\WpPlugin;

use DateTimeImmutable;
use LogicException;
use SplFileObject;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;

final class Stopwatch {

	private const MAX_FILESIZE = 1024 * 1024;

	private $instance;
	private $now;

	public function __construct() {
		$this->now = new DateTimeImmutable();
	}

	public function getStopwatch(): SymfonyStopwatch {
		return $this->instance ?? $this->instance = new SymfonyStopwatch();
	}

	public function dumpStopwatch(): void {
		if ( ! $this->instance ) {
			throw new LogicException( 'Stopwatch is not initialized.' );
		}

		$stopwatchLog = $this->getLogFileInfo();
		$splFileInfo  = new SplFileObject( $stopwatchLog, 'a' );
		if ( ! $splFileInfo->isWritable() ) {
			//TODO maybe somehow notify admin?
			return;
		}

		$this->pruneOldRecords( $splFileInfo );

		$data = [
			'timestamp' => $this->now->format( 'c' ),
		];
		foreach ( $this->instance->getSectionEvents( '__root__' ) as $event ) {
			$data[ $event->getName() ]['total'] = $event->getDuration();
			if ( 1 === count( $event->getPeriods() ) ) {
				continue;
			}
			$data[ $event->getName() ]['laps'] = [];
			foreach ( $event->getPeriods() as $period ) {
				$data[ $event->getName() ]['laps'][] = $period->getDuration();
			}
		}

		$splFileInfo->fwrite( json_encode( $data ) . PHP_EOL );
	}

	public function getStopwatchLog(): string {
		$file = $this->getLogFileInfo();
		if ( ! file_exists( $file ) ) {
			return '';
		}

		return file_get_contents( $file );
	}

	private function getLogFileInfo(): string {
		return get_temp_dir() . 'stopwatch.jsonl';
	}

	private function pruneOldRecords( SplFileObject $file ): void {
		if ( self::MAX_FILESIZE > $file->getSize() ) {
			return;
		}

		$data = file( $file->getPathname() );
		if ( ! $data ) {
			$file->ftruncate( 0 );
		}
		$data = array_slice( $data, 5 );

		if ( ! file_put_contents( $file->getPathname(), $data ) ) {
			$file->ftruncate( 0 );
		}
	}


}
