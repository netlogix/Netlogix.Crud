<?php
namespace Netlogix\Crud\Property\TypeConverter;

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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Netlogix\Crud\Domain\Model\DataTransfer\AbstractDataTransferObject;

/**
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Scope("singleton")
 */
class DataTransferObjectConverter extends ObjectConverter {

	/**
	 * @var \Neos\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @var array
	 */
	protected $sourceTypes = ['string', 'array'];

	/**
	 * @var string
	 */
	protected $targetType = AbstractDataTransferObject::class;

	/**
	 * @var int
	 */
	protected $priority = 5;

	/**
	 * Actually convert from $source to $targetType, taking into account the fully
	 * built $convertedChildProperties and $configuration.
	 *
	 * The return value can be one of three types:
	 * - an arbitrary object, or a simple type (which has been created while mapping).
	 *   This is the normal case.
	 * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
	 * - An instance of \Neos\Error\Messages\Error -- This will be a user-visible error message later on.
	 * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
	 *
	 * @param mixed $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return object the target type
	 * @throws \Neos\Flow\Property\Exception\InvalidTargetException
	 * @throws \InvalidArgumentException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($source === '') {
			/*
			 * Nothing to convert.
			 */
			return NULL;

		} elseif (!is_array($source) || isset($source['__identity'])) {
			/*
			 * This situation usually indicates that RealURL covers the payload param.
			 * Just add the payload directly to TypoLink and let this ObjectConverter
			 * handle the wrapping.
			 */
			if (is_array($source)) {
				$source['payload'] = [
					'__identity' => $source['__identity'],
				];
				unset($source['__identity']);

			} else {
				$source = [
					'payload' => [
						'__identity' => $source,
					],
				];

			}
			/** @var \Neos\Flow\Mvc\Controller\MvcPropertyMappingConfiguration $configuration */
			$configuration->allowProperties('payload');
			$configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
			return $this->propertyMapper->convert($source, $targetType, $configuration);

		} else {
			/*
			 * This is default. There either is no payload a tall, which means this
			 * is a POST request. Or there is one, which means this is a PUT request. But
			 * in both cases this should be an array that triggers several DTO setter
			 * methods.
			 */
			if (!isset($source['payload'])) {
				$source['payload'] = [
					'resource' => '',
				];
			}
			return parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);

		}
	}

	/**
	 * @param mixed $source
	 * @param string $targetType
	 * @return bool
	 */
	public function canConvertFrom($source, $targetType) {
		return TRUE;
	}

	/**
	 * Convert all properties in the source array
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (is_string($source)) {
			$source = [
				'payload' => [
					'__identity' => $source,
				]
			];
		} elseif (isset($source['__identity'])) {
			$source['payload'] = [
				'__identity' => $source['__identity'],
			];
			unset($source['__identity']);
		} elseif (!isset($source['payload'])) {
			$source['payload'] = [];
		}
		$source = parent::getSourceChildPropertiesToBeConverted($source);
		return $source;
	}

}
