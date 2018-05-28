<?php
namespace Netlogix\Crud\Property\TypeConverter;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class DateTimeConverter extends \Neos\Flow\Property\TypeConverter\DateTimeConverter {

	/**
	 * @var integer
	 */
	protected $priority = 100;

	/**
	 * Converts $source to a \DateTime using the configured dateFormat
	 *
	 * @param string|integer|array $source the string to be converted to a \DateTime object
	 * @param string $targetType must be "DateTime"
	 * @param array $convertedChildProperties not used currently
	 * @param \Neos\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \DateTime
	 * @throws \Neos\Flow\Validation\Error
	 * @throws \Exception
	 * @throws \Neos\Flow\Property\Exception\TypeConverterException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = [], \Neos\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$date = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);
		if ($date instanceof \Neos\Flow\Validation\Error && is_string($source)) {
			$newDate = new $targetType($source);
			if ($newDate !== FALSE) {
				$timeZone = (new $targetType)->getTimezone();
				$newDate->setTimezone($timeZone);
				$date = $newDate;
			}
		}

		return $date;
	}

}