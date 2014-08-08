<?php
namespace Netlogix\Crud\Domain\Service\MetaDataProcessor;

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
 * Enhances Json output by some meta data
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
abstract class AbstractMetaDataProcessor {

	/**
	 * Enhances the metaData property by additional data.
	 *
	 * Additional information can be any kind of data that is necessary for the client
	 * to process its model but does not belong to the domain data. Those can be e.g.
	 * the "readOnly" attribute or pre-fetched content of an Pointer.
	 *
	 * @param array $metaData
	 * @param string $propertyPath
	 * @param mixed $processedValue
	 * @param array $upstreamStorage
	 * @param mixed $object
	 * @param \TYPO3\Flow\Mvc\Routing\UriBuilder $uriBuilder
	 * @param string $metaDataProcessorGroup
	 * @return array
	 */
	abstract public function process($metaData, $propertyPath, $processedValue, array &$upstreamStorage, $object, $uriBuilder, $metaDataProcessorGroup);

}