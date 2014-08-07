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
 * Additional array utility

 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Scope("singleton")
 */
class ArrayUtility extends \TYPO3\Flow\Utility\Arrays {

	const SEGMENTATION_CHARACTER = '.';

	/**
	 * Converts a multidimensional array to a flat representation.
	 *
	 * See unit tests for more details
	 *
	 * Example:
	 * - array:
	 * array(
	 *   'first.' => array(
	 *     'second' => 1
	 *   )
	 * )
	 * - result:
	 * array(
	 *   'first.second' => 1
	 * )
	 *
	 * Example:
	 * - array:
	 * array(
	 *   'first' => array(
	 *     'second' => 1
	 *   )
	 * )
	 * - result:
	 * array(
	 *   'first.second' => 1
	 * )
	 *
	 * @param array $array The (relative) array to be converted
	 * @param string $prefix The (relative) prefix to be used (e.g. 'section.')
	 * @return array
	 */
	static public function flatten(array $array, $prefix = '', $keepEmptyArrayNode = TRUE, $segmentationCharacter = self::SEGMENTATION_CHARACTER) {
		$flatArray = array();
		foreach ($array as $key => $value) {
			// Ensure there is no trailling dot:
			$key = rtrim($key, $segmentationCharacter);
			if (!is_array($value) || (count($value) === 0 && $keepEmptyArrayNode)) {
				$flatArray[$prefix . $key] = $value;
			} else {
				$flatArray = array_merge($flatArray, self::flatten($value, $prefix . $key . $segmentationCharacter, $keepEmptyArrayNode));
			}
		}
		return $flatArray;
	}

	/**
	 * The very opposite of self::flatten()
	 *
	 * @param array $array
	 * @return array
	 */
	static public function unflatten(array $array, $segmentationCharacter = self::SEGMENTATION_CHARACTER) {
		ksort($array);
		$unflatArray = array();
		foreach ($array as $key => $value) {
			self::insertValueIntoStructureByPath(explode($segmentationCharacter, $key), $unflatArray, $value);
		}
		return $unflatArray;
	}

	/**
	 * insertValueIntoStructureByPath
	 *
	 * @param array<string> $path
	 * @param array $target
	 * @param mixed $value
	 * @return void
	 */
	static protected function insertValueIntoStructureByPath(array $path, &$target, $value) {
		$segment = array_shift($path);
		if (count($path) > 0) {
			if (!array_key_exists($segment, $target)) {
				$target[$segment] = array();
			}
			self::insertValueIntoStructureByPath($path, $target[$segment], $value);
		} else {
			$target[$segment] = $value;
		}
		ksort($target);
	}

}
