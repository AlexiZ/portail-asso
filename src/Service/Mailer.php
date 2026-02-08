<?php

namespace App\Service;

use App\Entity\Association;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Mailer
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly HttpClientInterface $client,
        private readonly string $brevoApiKey,
        private readonly string $mailerDevRecipient,
    ) {
    }

    public function resetPassword(User $user): void
    {
        $this->send([$user->getEmail()], [
            'SUBJECT' => $this->translator->trans('email.forgot_password.subject'),
            'BODY' => $this->twig->render('emails/security/forgot_password.html.twig', [
                'username' => $user->getUsername(),
                'resetUrl' => $this->urlGenerator->generate('app_reset_password', ['token' => $user->getResetToken()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]),
        ]);
    }

    public function subscriptionReport(User $user, array $data): void
    {
        $this->send([$user->getEmail()], [
            'SUBJECT' => $this->translator->trans('email.subscriptions_report.subject'),
            'BODY' => $this->twig->render('emails/subscriptions/report.html.twig', [
                'username' => $user->getUsername(),
                'data' => $data,
            ]),
        ]);
    }

    public function requestMembership(array $parameters): void
    {
        $this->send($parameters['to'], [
            'SUBJECT' => $this->translator->trans('email.request_membership.subject'),
            'BODY' => $this->twig->render('emails/membership/request.html.twig', [
                'username' => $parameters['username'],
                'association' => $parameters['association'],
            ]),
        ]);
    }

    public function acceptMembership(User $user, Association $association): void
    {
        $this->send([$user->getEmail()], [
            'SUBJECT' => $this->translator->trans('email.accept_membership.subject'),
            'BODY' => $this->twig->render('emails/membership/accept.html.twig', [
                'username' => $user->getUsername(),
                'association' => $association,
            ]),
        ]);
    }

    public function inviteUser(array $parameters): void
    {
        $this->send($parameters['to'], [
            'SUBJECT' => $this->translator->trans('email.invite_new.subject', ['association' => $parameters['association']->getName()]),
            'BODY' => $this->twig->render('emails/membership/invite.html.twig', [
                'association' => $parameters['association']->getName(),
                'token' => base64_encode($parameters['association']->getId().'|'.implode(',', $parameters['to'])),
            ]),
        ]);
    }

    private function send(array $to, array $parameters): void
    {
        $toEmail = [];
        foreach ($to as $email) {
            $toEmail[] = [
                'email' => $this->mailerDevRecipient ?: $email,
            ];
        }

        $this->client->request(
            'POST',
            'https://api.brevo.com/v3/smtp/email',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'api-key' => $this->brevoApiKey,
                ],
                'json' => [
                    'to' => $toEmail,
                    'templateId' => 1,
                    'params' => $parameters,
                ],
            ]
        );
    }
}
