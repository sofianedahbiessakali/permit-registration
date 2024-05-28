<?php

namespace App\Form;

use App\Entity\Etudiant;
use App\Entity\Instructeur;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'with_seconds' => false,
                'with_minutes' => true,
                'html5' => true,
                'data' => new \DateTime(), 
                'attr' => ['class' => 'form-control'],
            ])
            ->add('instructeur', EntityType::class, [
                'class' => Instructeur::class,
                'choice_label' => 'nomcomplet',
                'attr' => ['class' => 'form-select'],
            ])
        ;

        /*
        ->add('date', DateTimeType::class, [
            'date_widget' => 'single_text',
            'with_seconds' => false,
            'html5' => true,
            'data' => new \DateTime(),
            'widget'  => 'choice',
            'minutes' => array(0, 30),
            'attr' => ['class' => 'form-control'],
        ])
        */
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
