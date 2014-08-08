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
 *
 */
class UriPointerNestedObjectMetaDataProcessor extends \Netlogix\Crud\Domain\Service\MetaDataProcessor\AbstractMetaDataProcessor {

	/**
	 * @var \TYPO3\Flow\Http\Client\Browser
	 * @Flow\Inject
	 */
	protected $browser;

	/**
	 * @var \TYPO3\Flow\Http\Client\CurlEngine
	 * @Flow\Inject
	 */
	protected $browserRequestEngine;

	/**
	 * @var \Netlogix\Crud\Domain\Service\SerializationService
	 * @Flow\Inject
	 */
	protected $serializationService;

	/**
	 * @var \Netlogix\Crud\Domain\Service\DataTransferObjectFactory
	 * @Flow\Inject
	 */
	protected $dataTransferObjectFactory;

	/**
	 * Enhances the metaData property by additional data.
	 *
	 * This metaDataProcessor relies on the assumption that the given UriPointer
	 * just points to the "showAction" of another DTO. The remote showAction
	 * is not called as a sub request but the argument DTO is rendered directly.
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
	public function process($metaData, $propertyPath, $processedValue, array &$alreadyIncluded, $object, $uriBuilder, $metaDataProcessorGroup) {
		if (in_array($processedValue, $alreadyIncluded)) {
			return $metaData;
		}

		/** @var \Netlogix\Crud\Domain\Model\DataTransfer\UriPointer $originalValue */
		$originalValue = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($object, $propertyPath);
		if (!is_object($originalValue) || !($originalValue instanceof \Netlogix\Crud\Domain\Model\DataTransfer\UriPointer)) {
			return $metaData;
		}

		if (count($originalValue->getArguments()) > 1) {
			$content = $this->serializationService->process($originalValue->getArguments(), $uriBuilder, $metaDataProcessorGroup);

		} elseif (count($originalValue->getArguments()) === 1) {
			$object = current($originalValue->getArguments());

			if (is_object($object) && $object instanceof \Netlogix\Crud\Domain\Model\DataTransfer\AbstractDataTransferObject) {
				$dataTransferObject = $object;
			} else {
				if ($this->dataTransferObjectFactory->hasDataTransferObject($object)) {
					$dataTransferObject = $this->dataTransferObjectFactory->getDataTransferObject($object);
				}
			}
			$content = ($dataTransferObject === NULL) ? NULL : $this->serializationService->process($dataTransferObject, $uriBuilder, $metaDataProcessorGroup);

		}

		if ($content) {
			$metaData['content'] = $content;
		}

		$alreadyIncluded[] = $processedValue;

		return $metaData;
	}

}