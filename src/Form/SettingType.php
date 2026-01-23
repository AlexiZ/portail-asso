<?php

namespace App\Form;

use App\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Setting|null $setting */
            $setting = $event->getData();
            $form = $event->getForm();

            if (!$setting) {
                return;
            }

            $isEditable = $setting->isEditable();

            $form->add('key', TextType::class, [
                'label' => 'Paramètre',
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => !$isEditable,
                ],
            ]);
            $form->add('value', TextType::class, [
                'label' => 'Valeur',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
            $form->add('helpText', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => !$isEditable,
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }
}
