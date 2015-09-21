<?php
namespace Netlogix\Crud\Domain\Service;

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

use Doctrine\Common\Collections\ArrayCollection;
use Netlogix\Crud\Domain\Model\DataTransfer\DataTransferInterface;
use Netlogix\Crud\Domain\Model\DataTransfer\UriPointer;
use Netlogix\Crud\Utility\ArrayUtility;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Mvc\Routing\UriBuilder;
use TYPO3\Flow\Property\TypeConverter\DateTimeConverter;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Utility\Arrays;

/**
 * Transforms serializable targets to arrays that can be handled by json_serialize().
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Scope("singleton")
 */
class SerializationService {

	/**
	 * If MetaData is applied to a property, a new, virtual property is introduced whose
	 * name follows this pattern. It's created through "sprintf", so use "%s" as spacer
	 * for the corresponding original property.
	 *
	 * @var string
	 */
	protected $metaDataPropertyNamePattern = '%s#%s';

	/**
	 * @var UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * The property path is filled when hierarchically traversing through array types.
	 * This is meant to allow fine grained configuration of meta data and does not
	 * directly influence the actual processing, nor is it exposed to the output.
	 *
	 * @var array
	 */
	protected $propertyPath = [];

	/**
	 * @var ConfigurationManager
	 * @Flow\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 * @Flow\Inject
	 */
	protected $resourceManager;

	/**
	 * @ var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
	 */
	protected $cache;

	/**
	 * The CacheTagService is optional. Even though it's a singleton, it might
	 * not be available when Nxcacehtags isn't installed.
	 *
	 * On top of that, when calling the jsonEncode() or process() method adding
	 * the cacheTagService to this call indicates if the current serialization run
	 * is meant to influence the current page caching or not.
	 *
	 * @ var \Netlogix\Nxcachetags\Service\CacheTagService
	 */
	protected $cacheTagService;

	/**
	 * @var array
	 */
	protected $metaDataProcessors = [];

	/**
	 * This common storage is used by MetaDataProcessors to communicate between different calls.
	 * Each MetaDataProcessor has its unique slot to store data in. This property cleared when
	 * the rendering of a root object is completed.
	 *
	 * @var array
	 */
	protected $metaDataProcessorStorage = [];

	/**
	 * @var string
	 */
	protected $metaDataProcessorGroup = '';

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	private $formerConfigurationStack = [];

	/**
	 * Returns JsonEncode of the given object.
	 * If UriPointers are found in the DTO hierarchy, the UriBuilder is required to resolve
	 * them.
	 *
	 * @param $object
	 * @param UriBuilder $uriBuilder
	 * @param string $metaDataProcessorGroup
	 * @return string
	 */
	public function jsonEncode($object, UriBuilder $uriBuilder, $metaDataProcessorGroup = NULL) {

		return json_encode($this->process($object, $uriBuilder, $metaDataProcessorGroup));

	}

	/**
	 * Basically TearUp and TearDown of the processing environment.
	 * The processing mechanism is another method.
	 *
	 * @param $object
	 * @param UriBuilder $uriBuilder
	 * @param string $metaDataProcessorGroup
	 * @return mixed
	 */
	public function process($object, UriBuilder $uriBuilder, $metaDataProcessorGroup = NULL) {

		$formerConfigurationStackAttributes = array(
			'cacheTagService',
			'metaDataProcessors',
			'metaDataProcessorStorage',
			'uriBuilder',
			'propertyPath',
		);

		/*
		 * Remember previous state
		 */
		array_unshift($this->formerConfigurationStack, []);
		foreach ($formerConfigurationStackAttributes as $formerConfigurationStackAttribute) {
			$this->formerConfigurationStack[0][$formerConfigurationStackAttribute] = $this->{$formerConfigurationStackAttribute};
		}

		/*
		 * Initialize
		 */
		$this->uriBuilder = $uriBuilder;
		$this->propertyPath = [];
		$this->initializeMetaDataProcessors($metaDataProcessorGroup);

		/*
		if ($cacheTagService) {
			$this->cacheTagService = $cacheTagService;
			$this->cacheTagService->openEnvironment();
		}
		*/

		$result = $this->processInternal($object);
		$result = $this->applyMetaData($result, $object);

		/*
		if ($cacheTagService) {
			$this->cacheTagService->closeEnvironment();
			$this->cacheTagService = NULL;
		}
		*/

		/*
		 * Reset previous state
		 */
		foreach ($formerConfigurationStackAttributes as $formerConfigurationStackAttribute) {
			$this->{$formerConfigurationStackAttribute} = $this->formerConfigurationStack[0][$formerConfigurationStackAttribute];
		}
		array_shift($this->formerConfigurationStack);

		return $result;
	}

	/**
	 * Dispatcher which kind of processing has to be done.
	 *
	 * @param mixed $object
	 * @return mixed
	 */
	protected function processInternal($object) {
		if (is_scalar($object)) {
			return $object;
		} elseif (is_array($object)) {
			return $this->processCollectionType($object, array_keys($object));
		} elseif ($object instanceof \DateTime) {
			return $object->format(DateTimeConverter::DEFAULT_DATE_FORMAT);
		} elseif (is_object($object) && ($object instanceof ArrayCollection)) {
			$arrayRepresentation = $object->toArray();
			return $this->processCollectionType($arrayRepresentation, array_keys($arrayRepresentation));
		} elseif ($object instanceof DataTransferInterface) {
			if (!$this->cacheTagService) {
				$content = $this->processCollectionType($object, $object->getPropertyNamesToBeApiExposed());
			} else {
				$this->cacheTagService->openEnvironment();
				$this->cacheTagService->addEnvironmentCacheTag($object);
				$identifier = md5($this->cacheTagService->createCacheIdentifier([$this->metaDataProcessorGroup, $object]));

				if (!$this->cache->has($identifier)) {
					$content = $this->processCollectionType($object, $object->getPropertyNamesToBeApiExposed());
					$this->cache->set($identifier, $content, $this->cacheTagService->getEnvironmentTags());
				} else {
					$content = $this->cache->get($identifier);
				}
				$this->cacheTagService->closeEnvironment();
			}
			return $content;
		} elseif ($object instanceof UriPointer) {
			return $this->processUriPointer($object);
		} elseif ($object instanceof Resource) {
			return $this->resourceManager->getPublicPersistentResourceUri($object);
		}
	}

	/**
	 * This method handles "collection types", which are DTOs and arrays.
	 *
	 * Each individual property of those collection type is processed according
	 * internal processing rules.
	 * There might be MetaData content available for each property. Although those
	 * are applied to each collection type, those work best when being applied
	 * to Dto properties but not to array properties.
	 *
	 * array(
	 *     0 => array(
	 *         'subject' => 'foo',
	 *         'content' => 'bar',
	 *         'resource' => 'http://localhost/me',
	 *         'resource$$' => 'MetaData for the "resource" property, which is "0.resource".', // good
	 *     ),
	 *     '0$$' => 'MetaData for this object, which is "0".', // bad
	 * )
	 *
	 * This example shows that "resource" can be enhanced with MetaData easily because that
	 * does not change the model layout significantly. Having an additional object attribute
	 * in both, PHP and JavaScript doesn't change behavior.
	 * On the other side, adding meta data to the array ('0$$') directly makes JsonEncode
	 * convert the array to an object. Without the MetaData, the surrounding object gets
	 * encoded to '[{"subject":"x" ... }]', with having the MetaData applied, the surrounding
	 * object gets converted to '{"0":{},"0$$":"x"}'. Note that the encoded version of the
	 * surrounding object in the first situation has square brackets indication an array,
	 * the second situation has curly brackets indicating an array. So the output type of
	 * of the surrounding object changes from array to object, just because the object meta
	 * data feature is enabled.
	 *
	 * MetaData needs to be configured separately. By default, no MetaData is attached.
	 *
	 * @param $collection
	 * @param $collectionKeys
	 * @return array
	 */
	protected function processCollectionType($collection, $collectionKeys) {
		$result = [];
		foreach ($collectionKeys as $collectionKey) {
			array_push($this->propertyPath, $collectionKey);

			$value = ObjectAccess::getProperty($collection, $collectionKey);
			$result[$collectionKey] = $this->processInternal($value);

			array_pop($this->propertyPath);
		}
		return $result;
	}

	/**
	 * UriPointers are a special type, because they are meant to create a very specific and unique uri string.
	 *
	 * FIXME: Introduce a service that handles this uri building.
	 *
	 * @param UriPointer $uriPointer
	 * @return string
	 */
	protected function processUriPointer(UriPointer $uriPointer) {

		$uri = $this->uriBuilder
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor($uriPointer->getActionName(), $uriPointer->getArguments(), $uriPointer->getControllerName(), $uriPointer->getPackageKey(), $uriPointer->getSubPackageKey());
		return $uri;
	}

	/**
	 * Returns MetaData for the given property
	 *
	 * Additional information can be any kind of data that is necessary for the client
	 * to process its model but does not belong to the domain data. Those can be e.g.
	 * th "readOnly" attribute or pre-fetched content of an Pointer.
	 *
	 * FIXME: Define how to configure this.
	 * Maybe add configuration by $this->jsonEncode and $this->process that determines
	 * which property path shall be enhanced and which MetaData processor should be
	 * used.
	 *
	 * @param $propertyPath
	 * @param $processedValue
	 * @param mixed $object
	 * @return array
	 */
	protected function getMetaData($propertyPath, $processedValue, $object) {
		$metaData = [];

		foreach ($this->metaDataProcessors as $metaDataProcessorKey => $metaDataProcessorConfiguration) {
			if (!isset($this->metaDataProcessorStorage[$metaDataProcessorKey])) {
				$this->metaDataProcessorStorage[$metaDataProcessorKey] = [];
			}

			$path = $metaDataProcessorConfiguration['path'];

			if (preg_match($path, $propertyPath)) {
				/** @var \Netlogix\Crud\Domain\Service\MetaDataProcessor\AbstractMetaDataProcessor $processor */
				$processor = $metaDataProcessorConfiguration['processor'];
				$metaData = $processor->process($metaData, $propertyPath, $processedValue, $this->metaDataProcessorStorage[$metaDataProcessorKey], $object, $this->uriBuilder, $this->metaDataProcessorGroup);
			}
		}

		return $metaData;
	}

	/**
	 * The ApplyMetaData mechanism works through flattening the hierarchical
	 * structure to plain key/value pairs where the key represents the complete
	 * property path to the desired property.
	 *
	 * This is done by utilizing the http_build_query and parse_str methods,
	 * which take a whatever deep array and make it into a well formed string.
	 *
	 * @param array $result
	 * @param mixed $object
	 * @return array
	 */
	protected function applyMetaData($result, $object) {

		if (!is_array($result)) {
			return $result;
		}

		$flatResult = ArrayUtility::flatten($result);
		$additionalMetaData = [];

		foreach ($flatResult as $propertyPath => $value) {
			$metaData = $this->getMetaData($propertyPath, $value, $object);
			if ($metaData) {
				$additionalMetaData = array_merge($additionalMetaData, ArrayUtility::flatten($metaData, $propertyPath . '#'));
			}
		}

		if (!$additionalMetaData) {
			return $result;
		} else {
			$flatResult = array_merge($flatResult, $additionalMetaData);
			return ArrayUtility::unflatten($flatResult);
		}

	}

	/**
	 * initializeMetaDataProcessors
	 *
	 * @param string $metaDataProcessorGroup
	 * @return void
	 */
	protected function initializeMetaDataProcessors($metaDataProcessorGroup) {

		// FIXME: YAML configuration
		$this->metaDataProcessorGroup = $metaDataProcessorGroup;
		$this->metaDataProcessors = [];
		if (!$metaDataProcessorGroup) {
			return;
		}

		$settings = (array)$this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Netlogix.Crud');
		if (!isset($settings['metaDataProcessorGroups'][$metaDataProcessorGroup])) {
			return;
		}

		foreach (Arrays::trimExplode(',', $settings['metaDataProcessorGroups'][$metaDataProcessorGroup]) as $metaDataProcessorIndividual) {
			if (!isset($settings['metaDataProcessorIndividuals'][$metaDataProcessorIndividual])) {
				continue;
			}
			$metaDataProcessorIndividual = $settings['metaDataProcessorIndividuals'][$metaDataProcessorIndividual];
			if (!isset($metaDataProcessorIndividual['path']) || preg_match($metaDataProcessorIndividual['path'], '') === FALSE) {
				// FIXME: Exception
				continue;
			}
			if (!isset($metaDataProcessorIndividual['processor'])) {
				// FIXME: Exception
				continue;
			}
			$this->metaDataProcessors[] = [
				'path' => $metaDataProcessorIndividual['path'],
				'processor' => $this->objectManager->get($metaDataProcessorIndividual['processor'])
			];
		}
	}

}