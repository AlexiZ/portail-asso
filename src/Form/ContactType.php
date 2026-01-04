<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'contact.form.name',
                'label_attr' => [
                    'class' => 'my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
            ->add('function', TextType::class, [
                'label' => 'contact.form.function',
                'label_attr' => [
                    'class' => 'my-2',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'contact_entry';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
