<?php

declare(strict_types = 1);

namespace App\Form;

use App\Component\SharedEntity\PermissionsResolver\SharedEntityPermissionResolverInterface;
use App\Entity\EntityWithPermissionsInterface;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ShareType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
        private readonly SharedEntityPermissionResolverInterface $sharedEntityPermissionResolver,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Callback(function (string $payload, ExecutionContextInterface $context) {
                        $user =  $this->userRepository->findOneBy(['email' => $payload]);
                        if (null === $user) {
                            $context->buildViolation(sprintf('User with email %s not found', $payload))
                                ->atPath('email')
                                ->addViolation()
                            ;

                            return;
                        }

                        if ($this->security->getUser() === $user) {
                            $context->buildViolation('You already have access to the collection')
                                ->atPath('email')
                                ->addViolation()
                            ;

                            return;
                        }

                        $collection = $context->getRoot()->getData();
                        if (true === $this->sharedEntityPermissionResolver->canRead($user, $collection)) {
                            $context->buildViolation(sprintf('User with email %s already has access', $payload))
                                ->atPath('email')
                                ->addViolation()
                            ;
                        }
                    })
                ]
            ])
            ->add('permission', ChoiceType::class, [
                'mapped' => false,
                'choices' => [
                    'Read only' => EntityWithPermissionsInterface::READ_PERMISSION,
                    'Edit' => EntityWithPermissionsInterface::WRITE_PERMISSION,
                ],
            ])
            ->add('Share', SubmitType::class, ['label' => 'Share'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
