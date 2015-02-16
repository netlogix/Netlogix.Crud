<?php
namespace Netlogix\Crud\Domain\Model\DataTransfer;

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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @Flow\Scope("prototype")
 * @Flow\Entity
 * @ORM\InheritanceType("JOINED")
 */
abstract class AbstractDataTransferObject implements \Netlogix\Crud\Domain\Model\DataTransfer\DataTransferInterface {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 * @Flow\Inject
	 */
	protected $configurationManager;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 */
	protected $payload;

	/**
	 * @param Object $payload
	 * @return void
	 */
	public function __construct($payload) {
		$this->payload = $payload;
		$this->Persistence_Object_Identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface')->getIdentifierByObject($payload);
	}

	/**
	 * Returns all properties that should be exposed by JsonView
	 *
	 * @return array<string>
	 */
	abstract public function getPropertyNamesToBeApiExposed();

	/**
	 * This object is where this DataTransfer Object is wrapped around. It should *not* be
	 * exposed, because that is the only purpose of this DataTransfer Object object.
	 *
	 * @return Object
	 */
	abstract public function getPayload();

	/**
	 * preventPropertyModification
	 *
	 * @param $propertyName string
	 * @param $value mixed
	 * @return void
	 */
	protected function preventPropertyModification($propertyName, $value) {
		if ($this->getPropertyValue($propertyName) != $value) {
			throw new \TYPO3\Flow\Property\Exception(sprintf('The property "%s" must not be changed.', $propertyName), 1387294954);
		}
	}

	/**
	 * isPropertyManipulationReal
	 *
	 * @param $propertyName string
	 * @param $value mixed
	 * @return boolean
	 */
	protected function getPropertyValue($propertyName) {

		$originalValue = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($this, $propertyName);

		if ($originalValue instanceof \Netlogix\Crud\Domain\Model\DataTransfer\UriPointer) {
			/*
			 * UriPointer objects need to be transformed into the actual URI. Since this relies on
			 * an UriBuilder being bound to a \TYPO3\Flow\Http\Request object, this is no
			 * simple task at all without having a proper controller context.
			 * Only for the purpose of validation and change tracking we do this. Every other use
			 * case for UriPointer objects should make use of the surrounding controllers UriBuilder.
			 */
			return self::getUriForUriPointer($originalValue);
		}

		return $originalValue;
	}

	/**
	 * getUriForUriPointer
	 *
	 * @param \Netlogix\Crud\Domain\Model\DataTransfer\UriPointer $uriPointer
	 * @return string
	 */
	private static function getUriForUriPointer(\Netlogix\Crud\Domain\Model\DataTransfer\UriPointer $uriPointer) {

		static $uriBuilder;
		if (!$uriBuilder) {

			/** @var \TYPO3\Flow\Object\ObjectManagerInterface $objectManager */
			$objectManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager;

			/** @var \TYPO3\Flow\Http\Request $request */
			$request = new \TYPO3\Flow\Mvc\ActionRequest(\TYPO3\Flow\Http\Request::createFromEnvironment());

			/** @var \TYPO3\Flow\Mvc\Routing\UriBuilder $uriBuilder */
			$uriBuilder = $objectManager->get('TYPO3\\Flow\\Mvc\\Routing\\UriBuilder');
			$uriBuilder->setRequest($request);

		}

		$uri = $uriBuilder
			->reset()
			->setCreateAbsoluteUri(TRUE)
			->uriFor($uriPointer->getActionName(), $uriPointer->getArguments(), $uriPointer->getControllerName(), $uriPointer->getPackageKey(), $uriPointer->getSubPackageKey());

		return $uri;
	}

	/**
	 * Magic __call method
	 *
	 * If the called method is valid for the payload object, it just
	 * gets passed directly to it.
	 *
	 * @param string $methodName
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($methodName, $arguments) {
		if (is_callable(array($this->getPayload(), $methodName))) {
			return call_user_func_array(array($this->getPayload(), $methodName), $arguments);
		} elseif (substr($methodName, 0, 3) === 'get') {
			try {
				return \TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->getPayload(), lcfirst(substr($methodName, 3)));
			} catch (\TYPO3\Flow\Reflection\Exception\PropertyNotAccessibleException $e) {
				return NULL;
			}
		} else {
			return NULL;
		}
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface')->getIdentifierByObject($this->getPayload());
	}

}