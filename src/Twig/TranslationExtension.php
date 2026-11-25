<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Translation\TranslationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TranslationExtension extends AbstractExtension
{
    public function __construct(private readonly TranslationService $translationService)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('translate_content', function (?string $text, ?string $locale = null): string {
                return $this->translationService->translate((string) $text, $locale ?? 'en');
            }),
        ];
    }
}
