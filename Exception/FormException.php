<?php

namespace Opstalent\ApiBundle\Exception;

use Symfony\Component\Form\FormErrorIterator;

/**
 * {@inheritDoc}
 */
class FormException extends \Exception {

    protected $formErrors;

    /**
     * FormException constructor.
     * @param string $message
     * @param int $code
     * @param FormErrorIterator|null $formErrors
     */
    public function __construct($message = "", $code = 0, FormErrorIterator $formErrors = null)
    {
        parent::__construct($message, $code);
        $this->formErrors = $formErrors;
    }

    /**
     *
     */
    public function getFormErrors()
    {
        $formErrors = [];
        $numberOfErrors = $this->formErrors->getForm()->getErrors(true)->count();
        for ($iterator = 0; $numberOfErrors>$iterator; $iterator++) {
            $error = $this->formErrors->getForm()->getErrors(true)->current();
            $this->formErrors->getForm()->getErrors(true)->next();
            array_push($formErrors,[
                "message" => $error->getCause()->getMessage(),
                "propertyPath" => str_replace("data.","", $error->getCause()->getPropertyPath()),
            ]);
        }
        return $formErrors;
    }
}