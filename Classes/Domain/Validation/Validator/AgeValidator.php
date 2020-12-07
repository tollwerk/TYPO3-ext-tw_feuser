<?php
/**
 * Created by PhpStorm.
 * User: lucifer
 * Date: 29.01.2020
 * Time: 21:07
 */

namespace Tollwerk\TwUser\Domain\Validation\Validator;


use Tollwerk\TwBase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class AgeValidator
 * @package Tollwerk\TwUser\Domain\Validation\Validator
 */
class AgeValidator extends AbstractValidator
{
    const LOWER_THAN = 1;
    const LOWER_THAN_EQUAL = 2;
    const EQUAL = 4;
    const GREATER_THAN_EQUAL = 8;
    const GREATER_THAN = 16;

    /**
     * @var array
     */
    protected $supportedOptions = [
        'mode' => [self::GREATER_THAN_EQUAL, 'How to compare the given date and the required age', 'integer'],
        'age' => [18, 'The age to check against', 'integer'],
        'inputDateFormat' => ['Y-m-d', 'If the date is no DateTime object (for example, when the input field is not of type date), this format is used by DateTime::createFromFormat', 'Y-m-d'],
    ];

    /**
     * Check if a given date is at least as many years old as the minimal age
     * TODO: Should be able to check for other things than years
     *
     * @param mixed $value
     */
    public function isValid($value)
    {
        // Create DateTime object from form field value and today
        // Set time to 00:00 for proper validation.
        $date =  ($value instanceof \DateTime) ? $value : \DateTime::createFromFormat($this->options['inputDateFormat'], $value);
        $date->setTime(0, 0);
        $today = new \DateTime();
        $today->setTime(0, 0);

        // Calculate age in years
        $diff = $date->diff($today);
        $age = intval($diff->format('%Y'));

        // Validate
        $isValid = true;
        switch ($this->options['mode']) {
            case self::LOWER_THAN:
                $isValid = ($age < $this->options['age']);
                break;
            case self::LOWER_THAN_EQUAL:
                $isValid = ($age <= $this->options['age']);
                break;
            case self::EQUAL:
                $isValid = ($age == $this->options['age']);
                break;
            case self::GREATER_THAN_EQUAL:
                $isValid = ($age >= $this->options['age']);
                break;
            case self::GREATER_THAN:
                $isValid = ($age > $this->options['age']);
                break;
        }

        // If date/age is not valid, add a validation error
        if(!$isValid) {
            $this->result->addError(
                new Error(
                    LocalizationUtility::translate('validation.age.error.'.$this->options['mode'], 'TwUser', [$this->options['age']]),
                    1580330613
                )
            );
        }
    }
}
