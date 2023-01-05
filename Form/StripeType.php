<?php
namespace OMX\Ecommerce\StripePackage\Form;

use Symfony\Component\Form\AbstractType;

class StripeType extends AbstractType
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}