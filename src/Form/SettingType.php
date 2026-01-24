<?php

namespace App\Form;

use App\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Setting|null $setting */
        $setting = $builder->getData();
        $isEditable = !$setting || $setting->isEditable();

        $builder
            ->add('key', TextType::class, [
                'label' => 'Paramètre',
                'attr' => ['class' => 'form-control', 'readonly' => !$isEditable],
            ])
            ->add('value', TextType::class, [
                'label' => 'Valeur',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('helpText', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'readonly' => !$isEditable],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }
}
