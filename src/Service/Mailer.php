<?php

namespace App\Service;

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
        $this->send($user->getEmail(), [
            'SUBJECT' => $this->translator->trans('email.forgot_password.subject'),
            'BODY' => $this->twig->render('emails/security/forgot_password.html.twig', [
                'username' => $user->getUsername(),
                'resetUrl' => $this->urlGenerator->generate('app_reset_password', ['token' => $user->getResetToken()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]),
        ]);
    }

    public function subscriptionReport(User $user, array $data): void
    {
        $this->send($user->getEmail(), [
            'SUBJECT' => $this->translator->trans('email.subscriptions_report.subject'),
            'BODY' => $this->twig->render('emails/subscriptions/report.html.twig', [
                'username' => $user->getUsername(),
                'data' => $data,
            ]),
        ]);
    }

    private function send(string $to, array $parameters): void
    {
        $this->client->request(
            'POST',
            'https://api.brevo.com/v3/smtp/email',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'api-key' => $this->brevoApiKey,
                ],
                'json' => [
                    'to' => [
                        [
                            'email' => $this->mailerDevRecipient ?: $to,
                        ],
                    ],
                    'templateId' => 1,
                    'params' => $parameters,
                ],
            ]
        );
    }
}
