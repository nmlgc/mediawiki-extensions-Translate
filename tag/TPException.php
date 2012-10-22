<?php
/**
 * Translatable page parse exception.
 *
 * @file
 * @author Niklas Laxström
 * @copyright Copyright © 2009-2012 Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Class to signal syntax errors in translatable pages.
 *
 * @ingroup PageTranslation
 */
class TPException extends MWException {
	protected $msg = null;

	/**
	 * @todo Pass around Messages when Status class doesn't suck
	 * @param array $msg Message key with parameters
	 */
	public function __construct( array $msg ) {
		$this->msg = $msg;
		$wikitext = call_user_func_array( 'wfMessage', $msg )->text();
		parent::__construct( $wikitext );
	}

	/**
	 * @return array
	 */
	public function getMsg() {
		return $this->msg;
	}
}
