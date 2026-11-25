<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Entity\EntityTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TranslationService
{
    private const CACHE_ENTITY_TYPE = 'dynamic_text';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'TRANSLATION_API_KEY')] private readonly string $apiKey = '',
        #[Autowire(env: 'TRANSLATION_API_URL')] private readonly string $apiUrl = 'https://api-free.deepl.com/v2/translate',
    ) {
    }

    public function translate(string $text, string $targetLocale, string $sourceLocale = 'fr'): string
    {
        $text = trim($text);
        if ('' === $text || $targetLocale === $sourceLocale || !in_array($targetLocale, ['fr', 'en'], true)) {
            return $text;
        }

        $entityId = (int) hexdec(substr(hash('sha256', $sourceLocale.':'.$text), 0, 8));
        $repository = $this->entityManager->getRepository(EntityTranslation::class);
        $cached = $repository->findOneBy([
            'entityType' => self::CACHE_ENTITY_TYPE,
            'entityId' => $entityId,
            'locale' => $targetLocale,
            'field' => 'value',
        ]);
        if (null !== $cached) {
            return $cached->getValue();
        }

        if ('' === $this->apiKey) {
            return $text;
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'text' => [$text],
                    'source_lang' => strtoupper($sourceLocale),
                    'target_lang' => strtoupper($targetLocale),
                ],
                'timeout' => 5,
            ]);
            $data = $response->toArray(false);
            $translated = (string) ($data['translations'][0]['text'] ?? '');
        } catch (\Throwable) {
            return $text;
        }

        if ('' === $translated) {
            return $text;
        }

        $translation = new EntityTranslation();
        $translation->setEntityType(self::CACHE_ENTITY_TYPE)
            ->setEntityId($entityId)
            ->setLocale($targetLocale)
            ->setField('value')
            ->setValue($translated);
        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        return $translated;
    }
}
