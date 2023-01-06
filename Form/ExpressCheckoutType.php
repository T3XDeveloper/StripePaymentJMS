<?php
namespace JMS\Payment\StripeBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ExpressCheckoutType extends ExpressStripeType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', HiddenType::class, [
            'required' => false
        ]);
    }
}