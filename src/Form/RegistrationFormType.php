<?php

namespace App\Form;

use App\Entity\Association;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'attr' => [
                    'placeholder' => 'register.form.firstname',
                ],
                'required' => false,
            ])
            ->add('lastname', TextType::class, [
                'attr' => [
                    'placeholder' => 'register.form.lastname',
                ],
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'placeholder' => 'register.form.email',
                ],
                'required' => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'attr' => [
                        'placeholder' => 'register.form.password',
                    ],
                ],
                'second_options' => [
                    'attr' => [
                        'placeholder' => 'register.form.confirm_password',
                    ],
                ],
            ])
        ;

        if ($options['association']) {
            $builder->add('association', EntityType::class, [
                'mapped' => false,
                'class' => Association::class,
                'data' => $options['association'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'association' => null,
        ]);
    }
}
