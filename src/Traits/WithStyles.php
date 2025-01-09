<?php

namespace PBO\PbomlParser\Traits;

use PBO\PbomlParser\Style\StyleManager;

trait WithStyles
{
    protected ?StyleManager $styleManager = null;

    protected function getStyleManager(): StyleManager
    {
        if (! $this->styleManager) {
            $this->styleManager = new StyleManager;
        }

        return $this->styleManager;
    }

    protected function withStyles(string $content, string $type, array $options = []): string
    {
        return $this->getStyleManager()->apply($content, $type, $options);
    }

    protected function getStyleClasses(string $type, array $options = []): string
    {
        return $this->getStyleManager()->getStyles($type, $options);
    }

    protected function getThemeStyles(string $type): string
    {
        return $this->getStyleManager()->theme()->getThemeStyles($type);
    }

    protected function getPrintStyles(string $type): string
    {
        return $this->getStyleManager()->print()->getPrintStyles($type);
    }
}
