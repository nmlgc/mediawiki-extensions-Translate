<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\Translate\PageTranslation;

use MediaWiki\Extension\Translate\Jobs\GenericTranslateJob;
use MediaWiki\MediaWikiServices;
use MessageGroups;
use MessageGroupStats;
use MessageIndexRebuildJob;
use MessageUpdateJob;
use RunnableJob;
use Title;

/**
 * Job for updating translation units and translation pages when
 * a translatable page is marked for translation.
 */
class UpdateTranslatablePageJob extends GenericTranslateJob {
	/** @inheritDoc */
	public function __construct( Title $title, array $params = [] ) {
		parent::__construct( 'UpdateTranslatablePageJob', $title, $params );
	}

	/**
	 * Create a job that updates a translation page.
	 *
	 * If a list of sections is provided, then the job will also update translation
	 * unit pages.
	 *
	 * @param TranslatablePage $page
	 * @param TranslationUnit[] $sections
	 */
	public static function newFromPage( TranslatablePage $page, array $sections = [] ): self {
		$params = [];
		$params[ 'sections' ] = [];
		foreach ( $sections as $section ) {
			$params[ 'sections' ][] = $section->serializeToArray();
		}

		return new self( $page->getTitle(), $params );
	}

	public function run(): bool {
		// WARNING: Nothing here must not depend on message index being up to date.
		// For performance reasons, message index rebuild is run a separate job after
		// everything else is updated.

		// START: This section does not care about replication lag
		$this->logInfo( 'Starting UpdateTranslatablePageJob' );

		$sections = $this->params[ 'sections' ];
		foreach ( $sections as $index => $section ) {
			// Old jobs stored sections as objects because they were serialized and
			// unserialized transparently. That is no longer supported, so we
			// convert manually to primitive types first (to an PHP array).
			if ( is_array( $section ) ) {
				$sections[ $index ] = TranslationUnit::unserializeFromArray( $section );
			}
		}

		/**
		 * Units should be updated before the render jobs are run so that the
		 * latest changes can take effect on the translation pages.
		 */
		$page = TranslatablePage::newFromTitle( $this->title );
		$unitJobs = self::getTranslationUnitJobs( $page, $sections );
		foreach ( $unitJobs as $job ) {
			$job->run();
		}

		$this->logInfo(
			'Finished running ' . count( $unitJobs ) . ' MessageUpdate jobs for '
			. count( $sections ) . ' sections'
		);
		// END: This section does not care about replication lag
		$mwServices = MediaWikiServices::getInstance();
		$lb = $mwServices->getDBLoadBalancerFactory();
		if ( !$lb->waitForReplication() ) {
			$this->logWarning( 'Continuing despite replication lag' );
		}

		// Ensure we are using the latest group definitions. This is needed so
		// that in long running scripts we do see the page which was just
		// marked for translation. Otherwise getMessageGroup in the next line
		// returns null. There is no need to regenerate the global cache.
		MessageGroups::singleton()->clearProcessCache();
		// Ensure fresh definitions for stats
		$page->getMessageGroup()->clearCaches();

		$this->logInfo( 'Cleared caches' );

		// Refresh translations statistics, we want these to be up to date for the
		// RenderJobs, for displaying up to date statistics on the translation pages.
		$id = $page->getMessageGroupId();
		MessageGroupStats::forGroup(
			$id,
			MessageGroupStats::FLAG_NO_CACHE | MessageGroupStats::FLAG_IMMEDIATE_WRITES
		);
		$this->logInfo( 'Updated the message group stats' );

		// Try to avoid stale statistics on the base page
		$wikiPage = $mwServices->getWikiPageFactory()->newFromTitle( $page->getTitle() );
		$wikiPage->doPurge();
		$this->logInfo( 'Finished purging' );

		// These can be run independently and in parallel if possible
		$jobQueueGroup = $mwServices->getJobQueueGroup();
		$renderJobs = self::getRenderJobs( $page );
		$jobQueueGroup->push( $renderJobs );
		$this->logInfo( 'Added ' . count( $renderJobs ) . ' RenderJobs to the queue' );

		// Schedule message index update. Thanks to front caching, it is okay if this takes
		// a while (and on large wikis it does take a while!). Running it as a separate job
		// also allows de-duplication in case multiple translatable pages are being marked
		// for translation in a short period of time.
		$job = MessageIndexRebuildJob::newJob();
		$jobQueueGroup->push( $job );

		$this->logInfo( 'Finished UpdateTranslatablePageJob' );

		return true;
	}

	/**
	 * Creates jobs needed to create or update all translation unit definition pages.
	 * @param TranslatablePage $page
	 * @param TranslationUnit[] $units
	 * @return RunnableJob[]
	 */
	private static function getTranslationUnitJobs( TranslatablePage $page, array $units ): array {
		$jobs = [];

		$code = $page->getSourceLanguageCode();
		$prefix = $page->getTitle()->getPrefixedText();

		foreach ( $units as $unit ) {
			$unitName = $unit->id;
			$title = Title::makeTitle( NS_TRANSLATIONS, "$prefix/$unitName/$code" );

			$fuzzy = $unit->type === 'changed';
			$jobs[] = MessageUpdateJob::newJob( $title, $unit->getTextWithVariables(), $fuzzy );
		}

		return $jobs;
	}

	/**
	 * Creates jobs needed to create or update all translation pages.
	 * @return RunnableJob[]
	 */
	public static function getRenderJobs( TranslatablePage $page ): array {
		$jobs = [];

		$jobTitles = $page->getTranslationPages();
		// $jobTitles may have the source language title already but duplicate RenderTranslationPageJobs
		// are not executed so it's not run twice for the source language page present. This is
		// added to ensure that we create the source language page from the very beginning.
		$sourceLangTitle = $page->getTitle()->getSubpage( $page->getSourceLanguageCode() );
		$jobTitles[] = $sourceLangTitle;
		foreach ( $jobTitles as $t ) {
			$jobs[] = RenderTranslationPageJob::newJob( $t );
		}

		return $jobs;
	}

}