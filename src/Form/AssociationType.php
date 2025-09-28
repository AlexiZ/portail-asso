<?php

namespace App\Form;

use App\Entity\Association;
use App\Enum\Association\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssociationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'association.form.name',
            ])
        ;

        if (!$options['pre_new']) {
            $builder
                ->add('categories', ChoiceType::class, [
                    'choices' => Category::cases(),
                    'choice_value' => fn (?Category $category) => $category?->value,
                    'choice_label' => fn (Category $category) => 'association.category.'.$category->value,
                    'multiple' => true,
                    'required' => false,
                    'label' => 'association.form.categories',
                ])
                ->add('logo', FileType::class, [
                    'label' => 'association.form.logo',
                    'mapped' => false,
                    'required' => false,
                ])
                ->add('contactName', TextType::class, [
                    'label' => 'association.form.contact.name',
                    'required' => false,
                ])
                ->add('contactFunction', TextType::class, [
                    'label' => 'association.form.contact.function',
                    'required' => false,
                ])
                ->add('contactEmail', EmailType::class, [
                    'label' => 'association.form.contact.email',
                    'required' => false,
                ])
                ->add('contactPhone', TextType::class, [
                    'label' => 'association.form.contact.phone',
                    'required' => false,
                ])
                ->add('contactAddress', TextType::class, [
                    'label' => 'association.form.contact.address',
                    'required' => false,
                ])
                ->add('networkWebsite', TextType::class, [
                    'label' => 'association.form.network.website',
                    'required' => false,
                ])
                ->add('networkFacebook', TextType::class, [
                    'label' => 'association.form.network.facebook',
                    'required' => false,
                ])
                ->add('networkInstagram', TextType::class, [
                    'label' => 'association.form.network.instagram',
                    'required' => false,
                ])
                ->add('networkOther', TextType::class, [
                    'label' => 'association.form.network.other',
                    'required' => false,
                ])
                ->add('content', TextareaType::class, [
                    'label' => false,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Association::class,
            'pre_new' => false,
        ]);
    }
}
