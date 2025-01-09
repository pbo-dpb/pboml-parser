<?php

namespace PBO\PbomlParser\Traits;

use PBO\PbomlParser\Layout\LayoutManager;

trait WithLayout
{
    protected ?LayoutManager $layoutManager = null;

    protected function getLayoutManager(): LayoutManager
    {
        if (! $this->layoutManager) {
            $this->layoutManager = new LayoutManager;
        }

        return $this->layoutManager;
    }

    protected function withLayout(string $content, string $type, array $options = []): string
    {
        return $this->getLayoutManager()->wrap($content, $type, $options);
    }

    protected function getLayoutClasses(string $type, array $options = []): string
    {
        return $this->getLayoutManager()->getLayoutClasses($type, $options);
    }

    protected function getContainerClasses(string $type): string
    {
        return $this->getLayoutManager()->getContainerClasses($type);
    }

    protected function getGridClasses(array $options = []): string
    {
        return $this->getLayoutManager()->getGridClasses($options);
    }

    protected function getSpacingClasses(string $type, array $options = []): string
    {
        return $this->getLayoutManager()->getSpacingClasses($type, $options);
    }
}
