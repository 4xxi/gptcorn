<?php

namespace App\Form;

use App\Component\CollectionImport\Validator\Constraint\IsJsonOrCsvUrl;
use App\Entity\CollectionImport;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ImportCollectionType extends AbstractType
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxSize = $this->parameterBag->get('collection_import_max_file_size');

        $builder
            ->add('githubUrl', UrlType::class, [
                'label' => 'GitHub Url',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://github.com/user/repo/blob/main/data.json',
                ],
                'help' => 'Provide a direct link to a JSON or CSV file on Public GitHub repository.',
                'constraints' => [
                    new IsJsonOrCsvUrl(),
                ],
            ])
            ->add('fileUpload', FileType::class, [
                'label' => 'Upload a File',
                'required' => false,
                'help' => sprintf('Upload a JSON or CSV file. Maximum size: %s.', $maxSize),
                'constraints' => [
                    new File(
                        maxSize: $maxSize,
                        mimeTypes: ['application/json', 'text/csv', 'text/plain'],
                        mimeTypesMessage: 'Please upload a valid JSON or CSV file.'
                    ),
                ],
            ])
            ->add('Import', SubmitType::class, ['label' => 'Import'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => CollectionImport::class,
            ]
        );
    }
}
