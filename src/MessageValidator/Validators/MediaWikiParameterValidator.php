<?php
/**
 * @file
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Extensions\Translate\MessageValidator\Validators;

/**
 * An insertable wiki parameter validator that also acts as an InsertableSuggester
 * @since 2019.12
 */
class MediaWikiParameterValidator extends InsertableRegexValidator {
	public function __construct() {
		parent::__construct( '/\$[1-9]/' );
	}
}