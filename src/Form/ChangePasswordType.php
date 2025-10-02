<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'user_account.change_password.form.plain_password',
                ],
                'second_options' => [
                    'label' => 'user_account.change_password.form.confirm_password',
                ],
                'invalid_message' => 'user_account.change_password.form.error.password_mismatch',
                'constraints' => [
                    new NotBlank(['message' => 'user_account.change_password.form.constraint.password_not_blank']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'user_account.change_password.form.constraint.password_min_length',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }
}
