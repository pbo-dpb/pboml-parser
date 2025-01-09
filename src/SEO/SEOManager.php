<?php

namespace PBO\PbomlParser\SEO;

class SEOManager
{
    protected array $config;

    protected array $defaultMetaTags;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultMetaTags = [
            'viewport' => 'width=device-width, initial-scale=1',
            'format-detection' => 'telephone=no',
            'color-scheme' => 'light dark',
        ];
    }

    public function generateMetaTags(array $metadata): array
    {
        $metaTags = $this->defaultMetaTags;

        foreach (['en', 'fr'] as $locale) {
            $metaTags = array_merge($metaTags, [
                'title' => $this->generateTitle($metadata, $locale),
                'description' => $this->generateDescription($metadata, $locale),
                'keywords' => $this->generateKeywords($metadata, $locale),
                'author' => $metadata['author'][$locale] ?? null,
                'language' => $locale,
                'robots' => $this->generateRobotsDirectives($metadata),
            ]);

            $metaTags = array_merge($metaTags, [
                'og:type' => 'article',
                'og:site_name' => $this->config['site_name'] ?? '',
                'og:title' => $this->generateOgTitle($metadata, $locale),
                'og:description' => $this->generateOgDescription($metadata, $locale),
                'og:url' => $this->generateCanonicalUrl($metadata, $locale),
                'og:locale' => $this->getOgLocale($locale),
                'og:image' => $this->getOgImage($metadata, $locale),
                'og:image:width' => '1200',
                'og:image:height' => '630',
                'og:image:alt' => $this->getOgImageAlt($metadata, $locale),
            ]);

            $metaTags = array_merge($metaTags, [
                'twitter:card' => 'summary_large_image',
                'twitter:site' => $this->config['twitter_handle'] ?? '',
                'twitter:title' => $this->generateTwitterTitle($metadata, $locale),
                'twitter:description' => $this->generateTwitterDescription($metadata, $locale),
                'twitter:image' => $this->getTwitterImage($metadata, $locale),
                'twitter:image:alt' => $this->getTwitterImageAlt($metadata, $locale),
            ]);

            if ($this->isArticle($metadata)) {
                $metaTags = array_merge($metaTags, [
                    'article:published_time' => $this->formatDateTime($metadata['release_date'] ?? null),
                    'article:modified_time' => $this->formatDateTime($metadata['modified_date'] ?? null),
                    'article:author' => $metadata['author'][$locale] ?? null,
                    'article:section' => $metadata['type'][$locale] ?? null,
                    'article:tag' => $this->generateArticleTags($metadata, $locale),
                ]);
            }

            $metaTags['alternate'] = $this->generateLanguageAlternates($metadata);

            $metaTags = array_merge($metaTags, [
                'DC.title' => $metadata['title'][$locale] ?? '',
                'DC.description' => $this->generateDescription($metadata, $locale),
                'DC.publisher' => $this->config['publisher_name'] ?? '',
                'DC.language' => $locale,
                'DC.rights' => $metadata['copyright'][$locale] ?? '',
            ]);
        }

        return array_filter($metaTags);
    }

    public function generateStructuredData(array $metadata, string $locale): array
    {
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $this->generateCanonicalUrl($metadata, $locale),
            ],
            'headline' => $metadata['title'][$locale] ?? '',
            'description' => $this->generateDescription($metadata, $locale),
            'datePublished' => $this->formatDateTime($metadata['release_date'] ?? null),
            'dateModified' => $this->formatDateTime($metadata['modified_date'] ?? null),
            'author' => [
                '@type' => 'Organization',
                'name' => $metadata['author'][$locale] ?? $this->config['publisher_name'] ?? '',
                'url' => $metadata['url'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->config['publisher_name'] ?? '',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->config['publisher_logo'] ?? '',
                ],
                'url' => $metadata['url'],
            ],
            'inLanguage' => $locale,
            'copyrightYear' => $this->extractYear($metadata['copyright'][$locale] ?? ''),
            'copyrightHolder' => [
                '@type' => 'Organization',
                'name' => $this->config['publisher_name'] ?? '',
            ],
        ];

        if ($image = $this->getOgImage($metadata, $locale)) {
            $structuredData['image'] = [
                '@type' => 'ImageObject',
                'url' => $image,
                'width' => '1200',
                'height' => '630',
            ];
        }

        if (! empty($metadata['citations'])) {
            $structuredData['citation'] = $this->generateCitations($metadata['citations'], $locale);
        }

        return $structuredData;
    }

    public function generateBreadcrumbData(array $metadata, string $locale): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $this->config['site_name'] ?? '',
                    'item' => $metadata['url'],
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $metadata['type'][$locale] ?? '',
                    'item' => "{$metadata['url']}/{$locale}/type/".($metadata['type_slug'][$locale] ?? ''),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $metadata['title'][$locale] ?? '',
                    'item' => $this->generateCanonicalUrl($metadata, $locale),
                ],
            ],
        ];
    }

    protected function generateTitle(array $metadata, string $locale): string
    {
        $title = $metadata['title'][$locale] ?? '';
        $type = $metadata['type'][$locale] ?? '';
        $siteName = $this->config['site_name'] ?? '';

        return implode(' - ', array_filter([$title, $type, $siteName]));
    }

    protected function generateDescription(array $metadata, string $locale): string
    {
        return $metadata['description'][$locale]
            ?? substr(strip_tags($metadata['content'][$locale] ?? ''), 0, 160)
            ?? '';
    }

    protected function generateKeywords(array $metadata, string $locale): string
    {
        $keywords = $metadata['keywords'][$locale] ?? [];

        return is_array($keywords) ? implode(', ', $keywords) : $keywords;
    }

    protected function generateRobotsDirectives(array $metadata): string
    {
        $directives = ['index', 'follow'];

        if ($metadata['draft'] ?? false) {
            $directives = ['noindex', 'nofollow'];
        }

        if ($metadata['no_translate'] ?? false) {
            $directives[] = 'notranslate';
        }

        return implode(', ', $directives);
    }

    protected function generateOgTitle(array $metadata, string $locale): string
    {
        return $metadata['og_title'][$locale]
            ?? $metadata['title'][$locale]
            ?? '';
    }

    protected function generateOgDescription(array $metadata, string $locale): string
    {
        return $metadata['og_description'][$locale]
            ?? $this->generateDescription($metadata, $locale);
    }

    protected function generateTwitterTitle(array $metadata, string $locale): string
    {
        return $metadata['twitter_title'][$locale]
            ?? $this->generateOgTitle($metadata, $locale);
    }

    protected function generateTwitterDescription(array $metadata, string $locale): string
    {
        return $metadata['twitter_description'][$locale]
            ?? $this->generateOgDescription($metadata, $locale);
    }

    protected function generateCanonicalUrl(array $metadata, string $locale): string
    {
        $slug = $metadata['slug'][$locale] ?? $metadata['id'] ?? '';
        
        return "{$metadata['url']}/{$locale}/{$slug}";
    }

    protected function generateLanguageAlternates(array $metadata): array
    {
        $alternates = [];

        foreach (['en', 'fr'] as $lang) {
            if (isset($metadata['title'][$lang])) {
                $slug = $metadata['slug'][$lang] ?? $metadata['id'] ?? '';
                $alternates[$lang] = "{$metadata['url']}/{$lang}/{$slug}";
            }
        }

        return $alternates;
    }

    protected function getOgLocale(string $locale): string
    {
        return match ($locale) {
            'fr' => 'fr_CA',
            default => 'en_CA'
        };
    }

    protected function getOgImage(array $metadata, string $locale): ?string
    {
        return $metadata['og_image'][$locale]
            ?? $metadata['hero_image'][$locale]
            ?? $this->config['default_og_image']
            ?? null;
    }

    protected function getOgImageAlt(array $metadata, string $locale): string
    {
        return $metadata['og_image_alt'][$locale]
            ?? $metadata['title'][$locale]
            ?? '';
    }

    protected function getTwitterImage(array $metadata, string $locale): ?string
    {
        return $metadata['twitter_image'][$locale]
            ?? $this->getOgImage($metadata, $locale);
    }

    protected function getTwitterImageAlt(array $metadata, string $locale): string
    {
        return $metadata['twitter_image_alt'][$locale]
            ?? $this->getOgImageAlt($metadata, $locale);
    }

    protected function generateArticleTags(array $metadata, string $locale): array
    {
        return array_filter([
            $metadata['type'][$locale] ?? null,
            ...($metadata['tags'][$locale] ?? []),
        ]);
    }

    protected function generateCitations(array $citations, string $locale): array
    {
        return array_map(function ($citation) use ($locale) {
            return [
                '@type' => 'CreativeWork',
                'name' => $citation['title'][$locale] ?? '',
                'author' => $citation['author'][$locale] ?? '',
                'datePublished' => $citation['date'] ?? null,
                'url' => $citation['url'][$locale] ?? null,
            ];
        }, $citations);
    }

    protected function formatDateTime(?string $dateTime): ?string
    {
        if (! $dateTime) {
            return null;
        }

        return date('c', strtotime($dateTime));
    }

    protected function extractYear(string $copyright): string
    {
        preg_match('/\b\d{4}\b/', $copyright, $matches);

        return $matches[0] ?? date('Y');
    }

    protected function isArticle(array $metadata): bool
    {
        return true;
    }
}
