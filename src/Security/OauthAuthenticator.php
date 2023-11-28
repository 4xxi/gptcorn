<?php

declare(strict_types=1);

namespace App\Security;

use App\Component\File\AvatarFileHandlerInterface;
use App\Entity\EntityWithPermissionsInterface;
use App\Entity\SharedCollection;
use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class OauthAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    private const GOOGLE_OAUTH_ROUTE = 'connect_google_check';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly UserRepository $userRepository,
        private readonly AvatarFileHandlerInterface $fileHandler,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly CollectionRepository $collectionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return self::GOOGLE_OAUTH_ROUTE === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        /** @var 'connect_azure_check'|'connect_google_check' $route */
        $route = $request->attributes->get('_route');

        return match ($route) {
            self::GOOGLE_OAUTH_ROUTE => $this->authenticateGoogle(),
        };
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var string $oauthSuccessRedirectUrl */
        $oauthSuccessRedirectUrl = $this->parameterBag->get('oauth_success_redirect_url');

        return new RedirectResponse($oauthSuccessRedirectUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var string $oauthFailRedirectUrl */
        $oauthFailRedirectUrl = $this->parameterBag->get('oauth_fail_redirect_url');

        return new RedirectResponse($oauthFailRedirectUrl);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    private function authenticateGoogle(): Passport
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUser();

        $emailDomain = $this->getDomainFromEmail($googleUser->getEmail());
        if (true === $this->isDomainResctricted($emailDomain)) {
            throw new AuthenticationServiceException(
                sprintf('Sign up from the domain %s is not allowed.', $emailDomain)
            );
        }

        return new SelfValidatingPassport(
            new UserBadge((string) $googleUser->getEmail(), fn() => $this->fetchOrCreateUser($googleUser))
        );
    }

    private function fetchOrCreateUser(GoogleUser $googleUser): User
    {
        $email = (string) $googleUser->getEmail();

        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if (null === $existingUser) {
            $user = new User();
            $user->setEmail($googleUser->getEmail());
            $user->setName($googleUser->getName());
            $user->setPassword($this->passwordEncoder->hashPassword($user, uuid_create()));

            $avatar = $this->fileHandler->downloadAndSaveByUrl($googleUser->getAvatar());
            $user->setAvatar($avatar);

            $publicCollections = $this->collectionRepository->getPublic();
            foreach ($publicCollections as $publicCollection) {
                $sharedCollection = new SharedCollection();
                $sharedCollection->setCollection($publicCollection);
                $sharedCollection->setSharedWithUser($user);
                $sharedCollection->setSharedByUser($publicCollection->getMadePublicByUser());
                $sharedCollection->setPermissions(EntityWithPermissionsInterface::READ_PERMISSION);
                $this->entityManager->persist($sharedCollection);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $user;
        }

        if (null !== $existingUser->getAvatar()) {
            return $existingUser;
        }

        $avatar = $this->fileHandler->downloadAndSaveByUrl($googleUser->getAvatar());
        $existingUser->setAvatar($avatar);
        $this->userRepository->save($existingUser);

        return $existingUser;
    }

    private function getDomainFromEmail(string $email): string
    {
        return explode('@', $email)[1] ?? '';
    }

    private function isDomainResctricted(string $emailDomain): bool
    {
        $restrictedDomainsString = $this->parameterBag->get('sign_up_restricted_domains_list');
        $restrictedDomains = explode(',', $restrictedDomainsString);

        return in_array($emailDomain, $restrictedDomains, true);
    }
}
