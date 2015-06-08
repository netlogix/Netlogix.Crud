<?php
namespace Netlogix\Crud\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Netlogix.Crud".         *
 * It's a forward port of the TYPO3 extension EXT:nxcrudextbase           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Additional math utility
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Scope("singleton")
 */
class MathUtility {

	/**
	 * Tests if the input can be interpreted as integer.
	 *
	 * Note: Integer casting from objects or arrays is considered undefined and thus will return false.
	 *
	 * @see http://php.net/manual/en/language.types.integer.php#language.types.integer.casting.from-other
	 * @param mixed $var Any input variable to test
	 * @return boolean Returns TRUE if string is an integer
	 */
	static public function canBeInterpretedAsInteger($var) {
		if ($var === '' || is_object($var) || is_array($var)) {
			return FALSE;
		}
		return (string)intval($var) === (string)$var;
	}

}