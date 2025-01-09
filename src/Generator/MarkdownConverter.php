<?php

namespace PBO\PbomlParser\Generator;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\MarkdownConverter as CommonMarkConverter;
use PBO\PbomlParser\Generator\Utils\ReferenceProcessor;

class MarkdownConverter
{
    protected CommonMarkConverter $converter;
    protected array $footnoteReferences = [];
    protected ReferenceProcessor $referenceProcessor;

    public function __construct(string $locale = 'en')
    {
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'footnote' => [
                'backref_class' => 'footnote-backref',
                'backref_symbol' => '↩',
                'ref_class' => 'footnote-ref',
                'ref_id_prefix' => 'ref-'
            ],
            'table' => [
                'wrap' => [
                    'enabled' => true,
                    'tag' => 'div',
                    'attributes' => ['class' => 'pboml-non-reactive-prose'],
                ],
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new FootnoteExtension);

        $this->converter = new CommonMarkConverter($environment);
        $this->referenceProcessor = new ReferenceProcessor($locale);
    }

    public function convert(?string $markdown): string
    {
        if ($markdown === null) {
            return '';
        }

        $converted = $this->converter->convert($markdown)->getContent();

        return $this->referenceProcessor->process($converted);
    }

    public function getFootnoteReferences(): array
    {
        return $this->footnoteReferences;
    }
}
