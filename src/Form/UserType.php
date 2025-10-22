<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'user_account.edit.form.firstname',
                'required' => false,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'user_account.edit.form.lastname',
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'user_account.edit.form.plain_password',
                ],
                'second_options' => [
                    'label' => 'user_account.edit.form.confirm_password',
                ],
                'invalid_message' => 'user_account.edit.form.error.password_mismatch',
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'user_account.edit.form.constraint.password_min_length',
                        'max' => 4096,
                    ]),
                ],
                'required' => false,
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
