<?php

namespace PBO\PbomlParser\Providers;

use Exception;
use Illuminate\Support\ServiceProvider;
use PBO\PbomlParser\Exceptions\ParsingException;
use PBO\PbomlParser\Generator\HTMLGenerator;
use PBO\PbomlParser\Generator\Renderers\ChartRenderer;
use PBO\PbomlParser\Generator\Renderers\MarkdownRenderer;
use PBO\PbomlParser\Generator\Renderers\TableRenderer;
use PBO\PbomlParser\Generator\Renderers\HeadingRenderer;
use PBO\PbomlParser\Parser\PBOMLParser;

class PBOMLServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->register();
        $this->publishes([
            __DIR__ . '/config/charts.php' => config_path('charts.php'),
        ], 'pboml-charts');
    }

    /**
     * Generate HTML from a PBOML document
     *
     * @param  string  $document  Raw PBOML document content
     * @return string Generated HTML
     *
     * @throws ParsingException
     */
    public function generateHTML(string $document): string
    {
        try {
            $parser = $this->app->make('pboml.parser');
            $generator = $this->app->make('pboml.generator');

            $parsed = $parser->parse($document);

            return $generator->generate($parsed);
        } catch (Exception $e) {
            throw new ParsingException(
                'Failed to generate HTML from PBOML document: '.$e->getMessage(),
                ['document_size' => strlen($document)],
                $e
            );
        }
    }

    public function register(): void
    {
        $this->app->singleton('pboml.parser', function ($app) {
            return new PBOMLParser;
        });

        $this->app->singleton('pboml.generator', function ($app) {
            return new HTMLGenerator;
        });

        $this->app->singleton('pboml.markdown', function ($app) {
            return new MarkdownRenderer();
        });

        $this->app->singleton('pboml.table', function ($app) {
            return new TableRenderer();
        });

        $this->app->singleton('pboml.html', function ($app) {
            return new HTMLGenerator();
        });

        $this->app->singleton('pboml.heading', function ($app) {
            return new HeadingRenderer();
        });

        $this->app->singleton('pboml.charts', function ($app) {
            return new ChartRenderer();
        });

        $this->app->register(\ConsoleTVs\Charts\ChartsServiceProvider::class);
    }
}
