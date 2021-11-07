<?php
/**
 * File:        TransferType.php
 * Author:      Shawn Shiers
 */

namespace App\Form;


use App\Entity\Customer;
use App\Entity\Transfer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customer = $options['customerFrom'];

        $builder->add('customerTo',
            EntityType::class,
            [
                'label' => 'Transfer to',
                'class' => Customer::class,
                'query_builder' => function (EntityRepository $er) use ($customer){
                    return $er->createQueryBuilder('c')
                        ->where('c <> :this')
                        ->setParameter('this', $customer);
                },
                'mapped' => false
            ]
        );

        $builder->add(
            'amount',
            NumberType::class,
            [
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'step' => ".01"
                ]
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transfer::class,
        ])
        ->setRequired(['customerFrom']);
    }

    public function getBlockPrefix()
    {
        return 'form_transfer';
    }
}
