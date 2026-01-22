<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'event.form.title',
                'required' => true,
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'event.form.short_description',
                'attr' => [
                    'data-height' => '200',
                ],
                'required' => true,
            ])
            ->add('poster', FileType::class, [
                'label' => 'event.form.poster',
                'mapped' => false,
                'required' => false,
            ])
            ->add('longDescription', TextareaType::class, [
                'label' => 'event.form.long_description',
                'attr' => [
                    'data-height' => '400',
                ],
                'required' => true,
            ])
            ->add('startAt', DateTimeType::class, [
                'label' => 'event.form.start_at',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('recurrenceRule', HiddenType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'event.form.is_public.label',
                'required' => false,
                'help' => 'event.form.is_public.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
