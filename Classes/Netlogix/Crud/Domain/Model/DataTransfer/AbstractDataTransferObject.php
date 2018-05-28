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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\Exception;
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\ObjectAccess;

abstract class AbstractDataTransferObject implements DataTransferInterface {

	/**
	 * @var \Neos\Flow\ObjectManagement\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \Neos\Flow\Configuration\ConfigurationManager
	 * @Flow\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \Netlogix\Crud\Domain\Service\DataTransferObjectFactory
	 * @Flow\Inject
	 */
	protected $dataTransferObjectFactory;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 */
	protected $payload;

	/**
	 * @param Object $payload
	 */
	public function __construct($payload) {
		$this->payload = $payload;
		$this->Persistence_Object_Identifier = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->getIdentifierByObject($payload);
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
	 * @param string $propertyName
	 * @param mixed $value
	 * @throws Exception
	 */
	protected function preventPropertyModification($propertyName, $value) {
		if ($this->getPropertyValue($propertyName) != $value) {
			throw new Exception(sprintf('The property "%s" must not be changed.', $propertyName), 1387294954);
		}
	}

	/**
	 * isPropertyManipulationReal
	 *
	 * @param string $propertyName
	 * @return boolean
	 */
	protected function getPropertyValue($propertyName) {

		$originalValue = ObjectAccess::getProperty($this, $propertyName);

		if ($originalValue instanceof UriPointer) {
			/*
			 * UriPointer objects need to be transformed into the actual URI. Since this relies on
			 * an UriBuilder being bound to a \Neos\Flow\Http\Request object, this is no
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
	 * @param UriPointer $uriPointer
	 * @return string
	 */
	private static function getUriForUriPointer(UriPointer $uriPointer) {

		static $uriBuilder;
		if (!$uriBuilder) {
			/** @var \Neos\Flow\ObjectManagement\ObjectManagerInterface $objectManager */
			$objectManager = Bootstrap::$staticObjectManager;

			/** @var ActionRequest $request */
			$request = new ActionRequest(Request::createFromEnvironment());

			/** @var UriBuilder $uriBuilder */
			$uriBuilder = $objectManager->get(UriBuilder::class);
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
		if (is_callable([$this->getPayload(), $methodName])) {
			return call_user_func_array([$this->getPayload(), $methodName], $arguments);
		} elseif (substr($methodName, 0, 3) === 'get') {
			if (lcfirst(substr($methodName, 3)) == '__identity') {
				return $this->Persistence_Object_Identifier;
			}
			try {
				return ObjectAccess::getProperty($this->getPayload(), lcfirst(substr($methodName, 3)));
			} catch (PropertyNotAccessibleException $e) {
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
		return Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->getIdentifierByObject($this->getPayload());
	}

}
