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

use TYPO3\Flow\Annotations as Flow;

/**
 * Gets DTOs from given Non-DTOs.
 * This factory basically utilizes the property mapper to create objects.
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Scope("singleton")
 */
class DataTransferObjectFactory {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 * @Flow\Inject
	 */
	protected $propertyMapper;

	/**
	 * @var array
	 */
	protected $classMappingConfiguration = array();

	/**
	 * @var array
	 */
	protected $classNameToDtoClassNameReplaceFragments = array(
		'\\Domain\\Model\\DataTransfer\\' => '\\Domain\\Model\\',
		'\\Domain\\Model\\Dto\\' => '\\Domain\\Model\\',
	);

	/**
	 * Returns TRUE if there is a DataTransferObject class for the given
	 * class name, FALSE otherwise.
	 *
	 * @param $object
	 * @return bool
	 */
	public function hasDataTransferObject($object) {
		return ($this->getDataTransferObjectName($object) !== NULL);
	}

	/**
	 * Returns the class name of the given DataTransferObject.
	 *
	 * @param $object
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	public function getDataTransferObjectName($object) {
		$objectName = $this->reflectionService->getClassNameByObject($object);

		if (!array_key_exists($objectName, $this->classMappingConfiguration)) {
			foreach ($this->classNameToDtoClassNameReplaceFragments as $dtoClassNameFragment => $classNameFragment) {
				$dtoClassName = str_replace($classNameFragment, $dtoClassNameFragment, $objectName);
				if (class_exists($dtoClassName)) {
					$this->classMappingConfiguration[$objectName] = $dtoClassName;
					break;
				}
			}
		}

		if (!isset($this->classMappingConfiguration[$objectName])) {
			throw new \TYPO3\Flow\Property\Exception\InvalidTargetException(sprintf('There is no DTO class name for "%s" objects.', $objectName), 1407499486);
		}

		return $this->classMappingConfiguration[$objectName];
	}

	/**
	 * Returns a DataTransferObject for the given object name.
	 *
	 * @param mixed $object
	 * @return \Netlogix\Crud\Domain\Model\DataTransfer\AbstractDataTransferObject
	 */
	public function getDataTransferObject($object) {
		$identifier = $this->persistenceManager->getIdentifierByObject($object);
		$dto = $this->propertyMapper->convert($identifier, $this->getDataTransferObjectName($object));
		return $dto;
	}

	/**
	 * @param array<mixed> $objects
	 * @return array<\Netlogix\Crud\Domain\Model\DataTransfer\AbstractDataTransferObject>
	 */
	public function getDataTransferObjects($objects) {
		$result = array();
		foreach ($objects as $key => $object) {
			$result[$key] = $this->getDataTransferObject($object);
		}
		return $result;
	}

}