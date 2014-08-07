<?php
namespace Netlogix\Crud\View;

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
 * This JsonView expects a very specific "primary object" to be available inside of the
 * templateVariableContainer.
 * This primary object is required to be either array, scalar or  an instance of
 * \Netlogix\Crud\Domain\Model\DataTransfer\DataTransferInterface, as well as all exported sub
 * properties.
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class JsonView extends \TYPO3\Flow\Mvc\View\JsonView {

	/**
	 * @var \Netlogix\Crud\Domain\Service\SerializationService
	 * @Flow\Inject
	 */
	protected $serializationService;

	/**
	 * Loads the configuration and transforms the value to a serializable
	 * array.
	 *
	 * @return array An array containing the values, ready to be JSON encoded
	 * @api
	 */
	protected function renderArray() {
		$requestControllerActionIdentifier = $this->controllerContext->getRequest()->getControllerObjectName() . '\\' . $this->controllerContext->getRequest()->getControllerActionName() . 'Action';
		/** @var \TYPO3\Flow\Mvc\Controller\ControllerContext $controllerContext */
		if (count($this->variablesToRender) === 1) {
			$variableName = current($this->variablesToRender);
			return isset($this->variables[$variableName]) ? $this->serializationService->process($this->variables[$variableName], $this->controllerContext->getUriBuilder(), $requestControllerActionIdentifier) : NULL;
		} else {
			$result = array();
			foreach ($this->variablesToRender as $variableName) {
				$result[$variableName] = isset($this->variables[$variableName]) ? $this->serializationService->process($this->variables[$variableName], $this->controllerContext->getUriBuilder(), $requestControllerActionIdentifier) : NULL;
			}
			return $result;
		}
	}

}