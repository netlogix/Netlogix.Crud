<?php
namespace Netlogix\Crud\Controller;

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

use Netlogix\Crud\Domain\Model\DataTransfer\AbstractDataTransferObject;
use Netlogix\Crud\View\JsonView;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Fluid\View\TemplateView;

/**
 * An action controller for RESTful web services
 *
 * @package Netlogix.Crud
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class RestController extends \TYPO3\Flow\Mvc\Controller\RestController {

	/**
	 * The default view object to use if none of the resolved views can render
	 * a response for the current request.
	 *
	 * @var string
	 * @api
	 */
	protected $defaultViewObjectName = JsonView::class;

	/**
	 * A list of formats and object names of the views which should render them.
	 *
	 * @var array<string>
	 */
	protected $viewFormatToObjectNameMap = array(
		'html' => TemplateView::class,
		'json' => JsonView::class,
	);

	/**
	 * A list of IANA media types which are supported by this controller
	 *
	 * @var array
	 * @see http://www.iana.org/assignments/media-types/index.html
	 */
	protected $supportedMediaTypes = array('text/html', 'application/json');

	/**
	 * @Flow\Inject
	 * @var \Netlogix\Crud\Domain\Service\DataTransferObjectFactory
	 */
	protected $dataTransferObjectFactory;

	/**
	 * Determines the action method and assures that the method exists.
	 *
	 * @return string The action method name
	 * @throws \TYPO3\Flow\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function resolveActionMethodName() {
		$this->request->__previousControllerActionName = $this->request->getControllerActionName();
		return parent::resolveActionMethodName();
	}

	/**
	 * Implementation of the arguments initialization in the action controller:
	 * Automatically registers arguments of the current action
	 *
	 * Don't override this method - use initializeAction() instead.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException
	 * @see initializeArguments()
	 */
	public function initializeActionMethodArguments() {
		if ($this->request->__previousControllerActionName === 'index') {
			switch ($this->request->getHttpRequest()->getMethod()) {
				case 'POST' :
				case 'PUT':
					$arguments = $this->request->getArguments();
					if (!isset($arguments[$this->resourceArgumentName])) {
						$arguments[$this->resourceArgumentName] = [];
					}
					if ($this->isActionMethodArgumentDto($this->resourceArgumentName) && $this->request->hasArgument($this->resourceArgumentName)) {
						$payload = $this->request->getArgument($this->resourceArgumentName);
						$arguments[$this->resourceArgumentName] = $this->parseRequestBody();
						$arguments[$this->resourceArgumentName]['payload'] = $payload;
						unset($arguments[$this->resourceArgumentName][$this->resourceArgumentName]);
						unset($arguments[$this->resourceArgumentName]['__identity']);
					} else {
						$arguments[$this->resourceArgumentName] = array_merge_recursive($arguments[$this->resourceArgumentName], $this->parseRequestBody());
					}
				$this->request->setArguments($arguments);
				break;
			}
		}
		parent::initializeActionMethodArguments();
	}

	/**
	 * @return array
	 * @throws \TYPO3\Flow\Http\Exception
	 */
	protected function parseRequestBody() {
		$propertyMappingConfiguration = new PropertyMappingConfiguration();
		$propertyMappingConfiguration->setTypeConverter($this->objectManager->get(MediaTypeConverterInterface::class));
		$propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class, MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, $this->request->getHttpRequest()->getHeader('Content-Type'));
		return $this->objectManager->get(PropertyMapper::class)->convert($this->request->getHttpRequest()->getContent(), 'array', $propertyMappingConfiguration);
	}

	/**
	 * Allow modification of resources in createAction()
	 *
	 * @return void
	 */
	public function initializeCreateAction() {
		$this->initializePropertyMappingConfigurationForCreateActions();
	}

	/**
	 * Allow modification of resources in updateAction()
	 *
	 * @return void
	 */
	public function initializeUpdateAction() {
		$this->initializePropertyMappingConfigurationForUpdateActions();
	}

	/**
	 * Allow modification of resources in deleteAction()
	 *
	 * @return void
	 */
	public function initializeDeleteAction() {
		$this->initializePropertyMappingConfigurationForUpdateActions();
	}

	/**
	 * Allow modification of resources in showAction()
	 *
	 * @return void
	 */
	public function initializeShowAction() {
		$this->initializePropertyMappingConfigurationForShowActions();
	}

	/**
	 * Redirects the request to another action and / or controller.
	 *
	 * Redirect will be sent to the client which then performs another request to the new URI.
	 *
	 * NOTE: This method only supports web requests and will throw an exception
	 * if used with other request types.
	 *
	 * @param string $actionName Name of the action to forward to
	 * @param string $controllerName Unqualified object name of the controller to forward to. If not specified, the current controller is used.
	 * @param string $packageKey Key of the package containing the controller to forward to. If not specified, the current package is assumed.
	 * @param array $arguments Array of arguments for the target action
	 * @param integer $delay (optional) The delay in seconds. Default is no delay.
	 * @param integer $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other"
	 * @param string $format The format to use for the redirect URI
	 * @return void
	 * @throws StopActionException
	 * @see forward()
	 * @api
	 */
	protected function redirect($actionName, $controllerName = NULL, $packageKey = NULL, array $arguments = NULL, $delay = 0, $statusCode = 303, $format = NULL) {
		if ($format === NULL) {
			$format = '';
		}
		parent::redirect($actionName, $controllerName, $packageKey, $arguments, $delay, $statusCode, $format);
	}

	/**
	 * @return void
	 */
	protected function initializePropertyMappingConfigurationForShowActions() {

		/** @var \TYPO3\Flow\Mvc\Controller\Argument $argument */
		$argument = $this->arguments[$this->resourceArgumentName];

		$configuration = $argument->getPropertyMappingConfiguration();
		$configuration->allowProperties('payload');
	}

	/**
	 * @return void
	 */
	protected function initializePropertyMappingConfigurationForUpdateActions() {

		/** @var \TYPO3\Flow\Mvc\Controller\Argument $argument */
		$argument = $this->arguments[$this->resourceArgumentName];

		$configuration = $argument->getPropertyMappingConfiguration();
		$configuration->allowAllProperties();
		if ($this->request->getHttpRequest()->getMethod() === 'POST') {
			$configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
		} else {
			$configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED, TRUE);
		}
	}

	/**
	 * @return void
	 */
	protected function initializePropertyMappingConfigurationForCreateActions() {
		$this->initializePropertyMappingConfigurationForUpdateActions();

		/** @var \TYPO3\Flow\Mvc\Controller\Argument $argument */
		$argument = $this->arguments[$this->resourceArgumentName];

		$configuration = $argument->getPropertyMappingConfiguration()->forProperty('payload');
		$configuration->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, TRUE);
	}

	protected function isActionMethodArgumentDto($argumentName) {
		$actionMethodParameters = static::getActionMethodParameters($this->objectManager);
		if (isset($actionMethodParameters[$this->actionMethodName])) {
			$methodParameters = $actionMethodParameters[$this->actionMethodName];
		} else {
			$methodParameters = array();
		}

		return isset($methodParameters[$argumentName]) ? is_a($methodParameters[$argumentName]['type'], AbstractDataTransferObject::class, TRUE) : FALSE;
	}

	public function errorAction() {
		$validationResults = $this->arguments->getValidationResults()->getFlattenedErrors();
		$result = array();
		/** @var \TYPO3\Flow\Error\Error  $validationResult */
		foreach ($validationResults as $key => $validationResult) {
			/** @var \TYPO3\Flow\Validation\Error $error */
			foreach ($validationResult as $error) {
				$result['errors'][$key][] = array(
					'code' => $error->getCode(),
					'message' => $error->getMessage()
				);
			}
		}
		$result['success'] = false;
		$this->view->assign('value', $result);
		$this->response->setStatus(400);
	}

	/**
	 * @param object $payload
	 * @param integer $statusCode
	 * @throws StopActionException
	 * @throws \TYPO3\Flow\Mvc\Routing\Exception\MissingActionNameException
	 */
	protected function reportSuccess($payload, $statusCode = 200) {
		$this->uriBuilder->reset();
		$this->uriBuilder->setFormat($this->request->getFormat());

		$uri = $this->uriBuilder->setCreateAbsoluteUri(TRUE)->uriFor('index', array($this->resourceArgumentName => $payload), NULL, NULL, NULL);

		$this->response->setStatus($statusCode);
		$this->response->setHeader('Location', (string) $uri);

		$this->view->assign('value', $this->dataTransferObjectFactory->getDataTransferObject($payload));
		$this->response->setContent($this->view->render());

		throw new StopActionException();
	}

}