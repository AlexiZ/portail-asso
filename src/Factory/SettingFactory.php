<?php

namespace App\Factory;

use App\Dto\SettingsCollection;
use App\Entity\Setting;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;

readonly class SettingFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private SettingsService $settingService,
    ) {
    }

    public function update(SettingsCollection $dto, FormInterface $form): void
    {
        $initialSettings = $this->em->getRepository(Setting::class)->findAll();
        $initialIds = array_map(fn (Setting $s) => $s->getId(), $initialSettings);

        /** @var Setting[] $submitted */
        $submitted = $form->get('settings')->getData();

        $submittedIds = [];
        foreach ($submitted as $setting) {
            $this->em->persist($setting);
            if ($setting->getId()) {
                $submittedIds[] = $setting->getId();
            }
        }

        $idsToDelete = array_diff($initialIds, $submittedIds);

        if ($idsToDelete) {
            $repo = $this->em->getRepository(Setting::class);
            foreach ($idsToDelete as $id) {
                $this->em->remove($repo->find($id));
            }
        }

        $this->em->flush();

        /** @var Setting $setting */
        foreach ($dto->getSettings() as $setting) {
            $this->em->persist($setting);
            $this->settingService->refresh($setting->getKey(), $setting->getValue());
        }

        $this->em->flush();
    }
}
