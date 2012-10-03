<?php
/**
 * This file holds a message group interface.
 *
 * @file
 * @defgroup MessageGroup Message group
 * @author Niklas Laxström
 * @copyright Copyright © 2010-2012, Niklas Laxström
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Interface for message groups.
 *
 * Message groups are the heart of the Translate extension. They encapsulate
 * a set of messages each. Aside from basic information like id, label and
 * description, the class defines which mangler, message checker and file
 * system support (FFS), if any, the group uses.
 *
 * @ingroup MessageGroup
 */
interface MessageGroup {
	/**
	 * Returns the parsed YAML configuration.
	 * @todo Remove from the interface. Only usage is in FFS. Figure out a better way.
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * Returns the unique identifier for this group.
	 * @return string
	 */
	public function getId();

	/**
	 * Returns the human readable label (as plain text).
	 * @return string
	 */
	public function getLabel();

	/**
	 * Returns a longer description about the group. Description can use wiki text.
	 * @return string
	 */
	public function getDescription();

	/**
	 * Returns the namespace where messages are placed.
	 * @return int
	 */
	public function getNamespace();

	/**
	 * @todo Unclear usage. Perhaps rename to isSecondary with the only purpose
	 *       suppress warnings about message key conflicts.
	 * @return bool
	 */
	public function isMeta();

	/**
	 * If this function returns false, the message group is ignored and treated
	 * like it would not be configured at all. Useful for graceful degradation.
	 * Try to keep the check fast to avoid performance problems.
	 * @return bool
	 */
	public function exists();

	/**
	 * Returns a FFS object that handles reading and writing messages to files.
	 * May also return null if it doesn't make sense.
	 * @return FFS or null
	 */
	public function getFFS();

	/**
	 * Returns a message checker object or null.
	 * @todo Make an interface for message checkers.
	 * @return MessageChecker or null
	 */
	public function getChecker();

	/**
	 * Return a message mangler or null.
	 * @todo Make an interface for message manglers
	 * @return StringMatcher or null
	 */
	public function getMangler();

	/**
	 * Initialises a message collection with the given language code,
	 * message definitions and message tags.
	 * @param $code
	 * @return MessageCollection
	 */
	public function initCollection( $code );

	/**
	 * Returns a list of messages in a given language code. For some groups
	 * that list may be identical with the translation in the wiki. For other
	 * groups the messages may be loaded from a file (and differ from the
	 * current translations or definitions).
	 * @param $code
	 * @return array
	 */
	public function load( $code );

	/**
	 * Shortcut for load( getSourceLanguage() ).
	 */
	public function getDefinitions();

	/**
	 * Returns message tags. If type is given, only messages keys with that
	 * tag is returnted. Otherwise an array[tag => keys] is returnted.
	 * @param $type string
	 * @return array
	 */
	public function getTags( $type = null );

	/**
	 * Returns the definition or translation for given message key in given
	 * language code.
	 * @param $key string
	 * @param $code string
	 * @return string|null
	 */
	public function getMessage( $key, $code );

	/**
	 * Returns language code depicting the language of source text.
	 * @return string
	 */
	public function getSourceLanguage();

	/**
	 * Get the workflow configuration for the group.
	 */
	public function getWorkflowConfiguration();

	/**
	 * Get all the translatable languages for a group, considering the whitelisting
	 * and blacklisting.
	 * @return array|null The language codes as array keys.
	 */
	public function getTranslatableLanguages();
}
