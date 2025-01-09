<?php

namespace PBO\PbomlParser\SEO;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PBO\PbomlParser\Generator\EncodingHandler;

class AccessibilityManager
{
    use EncodingHandler;
    protected array $config;

    protected ?DOMDocument $dom = null;

    protected array $headingLevels = [];

    protected int $currentRegionId = 0;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function enhance(string $html): string
    {
        try {
            // First ensure proper UTF-8 encoding of input
            $html = $this->ensureUtf8s($html);
            $this->initializeDom($html);

            if (!$this->dom) {
                return $html;
            }

            $this->enhanceHeadings();
            $this->enhanceTables();
            $this->enhanceLinks();
            $this->enhanceForms();
            $this->enhanceImages();
            $this->addLandmarks();
            $this->addSkipLinks();
            $this->enhanceInteractiveElements();
            $this->validateDocumentStructure();

            // Get the body element to avoid doctype/html/head elements
            $body = $this->dom->getElementsByTagName('body')->item(0);
            if (!$body) {
                return $html;
            }

            // Save only the inner content of the body
            $output = '';
            foreach ($body->childNodes as $child) {
                $output .= $this->dom->saveHTML($child);
            }
            // Ensure proper UTF-8 encoding of output
            return $this->ensureUtf8s($output);
        } catch (\Exception $e) {
            error_log("Accessibility enhancement failed: " . $e->getMessage());
            return $html;
        }
    }

    /**
     * Get the accessibility tree representation
     */
    public function getAccessibilityTree(): array
    {
        $tree = [];
        $xpath = new DOMXPath($this->dom);

        // Get all elements with ARIA roles or semantic elements
        $elements = $xpath->query('//*[@role]|//header|//main|//nav|//footer|//article|//aside|//section');

        foreach ($elements as $element) {
            if (! ($element instanceof DOMElement)) {
                continue;
            }

            $tree[] = [
                'role' => $element->getAttribute('role') ?: $element->tagName,
                'name' => $this->getAccessibleName($element),
                'description' => $element->getAttribute('aria-description') ?: null,
                'state' => $this->getElementState($element),
            ];
        }

        return $tree;
    }

    protected function initializeDom(string $html): void
    {
        libxml_use_internal_errors(true);
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        // Set encoding before loading HTML
        $this->dom->encoding = 'UTF-8';
        // Add UTF-8 meta tag to ensure proper encoding
        $html = '<?xml encoding="UTF-8">' . $html;
        $this->dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
    }

    protected function setElementText(DOMElement $element, string $text): void
    {
        try {
            // Ensure proper encoding before setting text content
            $text = $this->ensureUtf8s($text);
            $element->textContent = $text;
        } catch (\Exception $e) {
            error_log("Failed to set element text: " . $e->getMessage());
        }
    }

    protected function enhanceHeadings(): void
    {
        $xpath = new DOMXPath($this->dom);
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');

        if ($headings === false) {
            return;
        }

        foreach ($headings as $heading) {
            if (! ($heading instanceof DOMElement)) {
                continue;
            }

            // Ensure proper heading hierarchy
            $level = (int) substr($heading->tagName, 1);
            $this->headingLevels[] = $level;

            // Add ID if not present
            if (! $heading->hasAttribute('id')) {
                $heading->setAttribute('id', $this->generateHeadingId($heading->textContent));
            }

            // Add aria-level for custom heading elements
            if ($heading->hasAttribute('role') && $heading->getAttribute('role') === 'heading') {
                $heading->setAttribute('aria-level', (string) $level);
            }
        }

        // Validate heading hierarchy
        $this->validateHeadingHierarchy();
    }

    protected function enhanceTables(): void
    {
        $tables = $this->dom->getElementsByTagName('table');

        foreach ($tables as $table) {
            if (! ($table instanceof DOMElement)) {
                continue;
            }

            // Add role if not present
            if (! $table->hasAttribute('role')) {
                $table->setAttribute('role', 'grid');
            }

            // Ensure caption exists
            if (! $this->hasCaption($table)) {
                $this->addTableCaption($table);
            }

            // Add scope to headers
            $headers = $table->getElementsByTagName('th');
            foreach ($headers as $header) {
                if (! ($header instanceof DOMElement)) {
                    continue;
                }

                if (! $header->hasAttribute('scope')) {
                    $header->setAttribute('scope', $this->determineHeaderScope($header));
                }
            }

            // Add aria-label to cells with special content
            $cells = $table->getElementsByTagName('td');
            foreach ($cells as $cell) {
                if (! ($cell instanceof DOMElement)) {
                    continue;
                }

                $this->enhanceTableCell($cell);
            }
        }
    }

    protected function enhanceLinks(): void
    {
        $links = $this->dom->getElementsByTagName('a');

        foreach ($links as $link) {
            if (! ($link instanceof DOMElement)) {
                continue;
            }

            // Add aria-label for links without text
            if (! trim($link->textContent)) {
                $title = $link->hasAttribute('title') ? $link->getAttribute('title') : 'Link';
                $link->setAttribute('aria-label', $title);
            }

            // Add target description for external links
            if ($link->hasAttribute('target') && $link->getAttribute('target') === '_blank') {
                $currentLabel = $link->hasAttribute('aria-label')
                    ? $link->getAttribute('aria-label')
                    : $link->textContent;

                $link->setAttribute(
                    'aria-label',
                    trim($currentLabel.' (opens in new window/tab)')
                );
            }

            // Add keyboard interaction attributes
            if (! $link->hasAttribute('tabindex')) {
                $link->setAttribute('tabindex', '0');
            }

            // Add download indication if present
            if ($link->hasAttribute('download')) {
                $link->setAttribute(
                    'aria-label',
                    trim($link->textContent.' (download)')
                );
            }
        }
    }

    protected function enhanceForms(): void
    {
        $forms = $this->dom->getElementsByTagName('form');

        foreach ($forms as $form) {
            if (! ($form instanceof DOMElement)) {
                continue;
            }

            // Add form landmarks
            if (! $form->hasAttribute('aria-label')) {
                $form->setAttribute('role', 'form');
                $form->setAttribute('aria-label', 'Form');
            }

            // Enhance form controls
            $controls = $form->getElementsByTagName('*');
            foreach ($controls as $control) {
                if (! ($control instanceof DOMElement)) {
                    continue;
                }

                if (in_array($control->tagName, ['input', 'select', 'textarea'])) {
                    $this->enhanceFormControl($control);
                }
            }
        }
    }

    protected function enhanceImages(): void
    {
        $images = $this->dom->getElementsByTagName('img');

        foreach ($images as $image) {
            if (! ($image instanceof DOMElement)) {
                continue;
            }

            // Ensure alt attribute exists
            if (! $image->hasAttribute('alt')) {
                $image->setAttribute('alt', '');
            }

            // Add loading attribute for performance
            if (! $image->hasAttribute('loading')) {
                $image->setAttribute('loading', 'lazy');
            }

            // Add decoding attribute for performance
            if (! $image->hasAttribute('decoding')) {
                $image->setAttribute('decoding', 'async');
            }

            // Handle decorative images
            if ($this->isDecorativeImage($image)) {
                $image->setAttribute('role', 'presentation');
                $image->setAttribute('alt', '');
            }
        }
    }

    protected function addLandmarks(): void
    {
        $xpath = new DOMXPath($this->dom);

        // Add main landmark if not present
        $main = $xpath->query('//main');
        if ($main->length === 0) {
            $this->wrapContentWithMain();
        }

        // Add navigation landmark
        $nav = $xpath->query('//nav');
        if ($nav->length === 0 && ($mainNav = $xpath->query('//ul[@class="main-nav"]'))->length > 0) {
            $this->wrapElementWithNav($mainNav->item(0));
        }

        // Add complementary landmarks
        $asides = $xpath->query('//aside');
        foreach ($asides as $aside) {
            if ($aside instanceof DOMElement && ! $aside->hasAttribute('role')) {
                $aside->setAttribute('role', 'complementary');
            }
        }
    }

    protected function addSkipLinks(): void
    {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return;
        }

        $skipLink = $this->dom->createElement('a');
        $skipLink->setAttribute('class', 'skip-link sr-only focus:not-sr-only');
        $skipLink->setAttribute('href', '#main-content');
        $this->setElementText($skipLink, 'Skip to main content');

        if ($body->firstChild) {
            $body->insertBefore($skipLink, $body->firstChild);
        } else {
            $body->appendChild($skipLink);
        }
    }

    protected function enhanceInteractiveElements(): void
    {
        $xpath = new DOMXPath($this->dom);

        // Fix the XPath query syntax
        // Changed from '//button|//[role="button"]' to '//button|//*[@role="button"]'
        $buttons = $xpath->query('//button|//*[@role="button"]');

        if ($buttons === false) {
            return;
        }

        foreach ($buttons as $button) {
            if (! ($button instanceof DOMElement)) {
                continue;
            }

            if (! $button->hasAttribute('type')) {
                $button->setAttribute('type', 'button');
            }

            if (! $button->hasAttribute('aria-label') && ! trim($button->textContent)) {
                $button->setAttribute('aria-label', 'Button');
            }
        }

        // Fix the XPath query for custom controls as well
        $customControls = $xpath->query('//*[@role="tabpanel" or @role="dialog" or @role="menu"]');

        if ($customControls === false) {
            return;
        }

        foreach ($customControls as $control) {
            if (! ($control instanceof DOMElement)) {
                continue;
            }

            $this->enhanceCustomControl($control);
        }
    }

    protected function validateDocumentStructure(): void
    {
        // Check heading hierarchy
        $this->validateHeadingHierarchy();

        // Check landmark presence
        $this->validateLandmarks();

        // Check for duplicate IDs
        $this->validateUniqueIds();
    }

    // Helper methods
    protected function generateHeadingId(string $text): string
    {
        try {
            // Ensure text is properly encoded before generating ID
            $text = $this->ensureUtf8s($text);
            $id = strtolower($text);
            $id = preg_replace('/[^a-z0-9]+/u', '-', $id); // Note the 'u' flag for UTF-8
            $id = trim($id,
                '-'
            );

            return 'heading-' . $id;
        } catch (\Exception $e) {
            // Fallback to a safe ID if encoding fails
            return 'heading-' . uniqid();
        }
    }

    protected function hasCaption(DOMElement $table): bool
    {
        return $table->getElementsByTagName('caption')->length > 0;
    }

    protected function addTableCaption(DOMElement $table): void
    {
        $caption = $this->dom->createElement('caption');
        $caption->setAttribute('class', 'sr-only');
        $this->setElementText($caption, 'Table');
        $table->insertBefore($caption, $table->firstChild);
    }

    protected function determineHeaderScope(DOMElement $header): string
    {
        $parent = $header->parentNode;
        if ($parent instanceof DOMElement) {
            return $parent->tagName === 'tr' ? 'col' : 'row';
        }

        // Default to 'col' if parent structure is unclear
        return 'col';
    }

    protected function enhanceTableCell(DOMElement $cell): void
    {
        // Add aria-label for cells with special content
        if ($cell->getElementsByTagName('*')->length > 0) {
            $cell->setAttribute('aria-label', $cell->textContent);
        }

        // Add aria-sort for sortable headers
        if ($cell->hasAttribute('data-sort')) {
            $cell->setAttribute('aria-sort', 'none');
        }
    }

    protected function enhanceFormControl(DOMElement $control): void
    {
        // Ensure label association
        if ($control->hasAttribute('id')) {
            $id = $control->getAttribute('id');
            if (! $this->hasAssociatedLabel($id)) {
                $this->createLabel($control);
            }
        } else {
            $id = 'control-'.uniqid();
            $control->setAttribute('id', $id);
            $this->createLabel($control);
        }

        // Add required attribute and aria-required
        if ($control->hasAttribute('required')) {
            $control->setAttribute('aria-required', 'true');
        }

        // Add aria-invalid for form validation
        if ($control->hasAttribute('data-invalid')) {
            $control->setAttribute('aria-invalid', 'true');
        }
    }

    protected function hasAssociatedLabel(string $id): bool
    {
        $xpath = new DOMXPath($this->dom);

        return $xpath->query("//label[@for='{$id}']")->length > 0;
    }

    protected function createLabel(DOMElement $control): void
    {
        $label = $this->dom->createElement('label');
        $label->setAttribute('for', $control->getAttribute('id'));

        $placeholder = $control->hasAttribute('placeholder')
        ? $control->getAttribute('placeholder')
        : ucfirst($control->getAttribute('name') ?? 'Input');

        $this->setElementText($label, $this->ensureUtf8s($placeholder));
        $control->parentNode->insertBefore($label, $control);
    }

    protected function isDecorativeImage(DOMElement $image): bool
    {
        return $image->hasAttribute('data-decorative') ||
            str_contains($image->getAttribute('class'), 'decorative');
    }

    protected function wrapContentWithMain(): void
    {
        $xpath = new DOMXPath($this->dom);
        $content = $xpath->query('//body/*');

        if ($content->length === 0) {
            return;
        }

        $main = $this->dom->createElement('main');
        $main->setAttribute('id', 'main-content');
        $main->setAttribute('role', 'main');

        $body = $this->dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return;
        }

        foreach ($content as $node) {
            $main->appendChild($node->cloneNode(true));
        }

        $body->textContent = '';
        $body->appendChild($main);
    }

    protected function wrapElementWithNav(DOMElement $element): void
    {
        $nav = $this->dom->createElement('nav');
        $nav->setAttribute('role', 'navigation');
        $nav->setAttribute('aria-label', 'Main navigation');

        $parent = $element->parentNode;
        $parent->insertBefore($nav, $element);
        $nav->appendChild($element);
    }

    protected function enhanceCustomControl(DOMElement $control): void
    {
        $role = $control->getAttribute('role');

        switch ($role) {
            case 'tabpanel':
                $this->enhanceTabPanel($control);
                break;
            case 'dialog':
                $this->enhanceDialog($control);
                break;
            case 'menu':
                $this->enhanceMenu($control);
                break;
        }
    }

    protected function enhanceTabPanel(DOMElement $panel): void
    {
        if (! $panel->hasAttribute('aria-labelledby')) {
            $id = 'tab-'.++$this->currentRegionId;
            $panel->setAttribute('aria-labelledby', $id);

            // Create associated tab if not exists
            $tab = $this->dom->createElement('button');
            $tab->setAttribute('id', $id);
            $tab->setAttribute('role', 'tab');
            $tab->setAttribute('aria-controls', $panel->getAttribute('id') ?? '');
            $panel->parentNode->insertBefore($tab, $panel);
        }
    }

    protected function enhanceDialog(DOMElement $dialog): void
    {
        $dialog->setAttribute('aria-modal', 'true');

        if (! $dialog->hasAttribute('aria-labelledby')) {
            $heading = $dialog->getElementsByTagName('h1')->item(0) ??
                $dialog->getElementsByTagName('h2')->item(0);

            if ($heading && $heading instanceof DOMElement) {
                $id = 'dialog-'.++$this->currentRegionId;
                $heading->setAttribute('id', $id);
                $dialog->setAttribute('aria-labelledby', $id);
            }
        }
    }

    protected function enhanceMenu(DOMElement $menu): void
    {
        if (! $menu->hasAttribute('aria-label')) {
            $menu->setAttribute('aria-label', 'Menu');
        }

        $items = $menu->getElementsByTagName('*');
        foreach ($items as $item) {
            if ($item instanceof DOMElement && ! $item->hasAttribute('role')) {
                $item->setAttribute('role', 'menuitem');

                if (! $item->hasAttribute('tabindex')) {
                    $item->setAttribute('tabindex', '-1');
                }
            }
        }
    }

    protected function validateHeadingHierarchy(): void
    {
        $previousLevel = 1;
        foreach ($this->headingLevels as $level) {
            // Heading levels should not skip levels
            if ($level > $previousLevel + 1) {
                $this->logAccessibilityWarning(
                    "Heading level skipped from h{$previousLevel} to h{$level}"
                );
            }
            $previousLevel = $level;
        }
    }

    protected function validateLandmarks(): void
    {
        $xpath = new DOMXPath($this->dom);

        // Check for main landmark
        if ($xpath->query('//main|//*[@role="main"]')->length === 0) {
            $this->logAccessibilityWarning('No main landmark found');
        }

        // Check for navigation landmark
        if ($xpath->query('//nav|//*[@role="navigation"]')->length === 0) {
            $this->logAccessibilityWarning('No navigation landmark found');
        }

        // Check for complementary landmarks
        if ($xpath->query('//aside|//*[@role="complementary"]')->length === 0) {
            $this->logAccessibilityWarning('No complementary landmarks found');
        }
    }

    protected function validateUniqueIds(): void
    {
        $xpath = new DOMXPath($this->dom);
        $elements = $xpath->query('//*[@id]');
        $ids = [];

        foreach ($elements as $element) {
            if (! ($element instanceof DOMElement)) {
                continue;
            }

            $id = $element->getAttribute('id');
            if (isset($ids[$id])) {
                $this->logAccessibilityWarning("Duplicate ID found: {$id}");
                // Generate new unique ID
                $newId = $id.'-'.uniqid();
                $element->setAttribute('id', $newId);
            }
            $ids[$id] = true;
        }
    }

    protected function logAccessibilityWarning(string $message): void
    {
        if (isset($this->config['log_warnings']) && $this->config['log_warnings']) {
            error_log("Accessibility Warning: {$message}");
        }
    }

    /**
     * Add keyboard navigation support to interactive elements
     */
    protected function addKeyboardSupport(DOMElement $element): void
    {
        if (! $element->hasAttribute('tabindex')) {
            $element->setAttribute('tabindex', '0');
        }

        // Add keyboard event handlers
        $this->addKeyboardEventHandlers($element);
    }

    /**
     * Add keyboard event handlers for interactive elements
     */
    protected function addKeyboardEventHandlers(DOMElement $element): void
    {
        $role = $element->getAttribute('role');

        switch ($role) {
            case 'button':
                $element->setAttribute(
                    'onkeydown',
                    "if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click()}"
                );
                break;

            case 'tab':
                $element->setAttribute(
                    'onkeydown',
                    "if(event.key==='ArrowRight'){event.preventDefault();this.nextElementSibling?.focus()}".
                        "if(event.key==='ArrowLeft'){event.preventDefault();this.previousElementSibling?.focus()}"
                );
                break;

            case 'menuitem':
                $element->setAttribute(
                    'onkeydown',
                    "if(event.key==='ArrowDown'){event.preventDefault();this.nextElementSibling?.focus()}".
                        "if(event.key==='ArrowUp'){event.preventDefault();this.previousElementSibling?.focus()}"
                );
                break;
        }
    }

    /**
     * Add live region support for dynamic content
     */
    protected function addLiveRegion(DOMElement $element, string $type = 'polite'): void
    {
        $element->setAttribute('aria-live', $type);
        $element->setAttribute('role', 'status');

        if ($type === 'assertive') {
            $element->setAttribute('aria-atomic', 'true');
        }
    }

    /**
     * Add focus management for modal dialogs
     */
    protected function addFocusManagement(DOMElement $dialog): void
    {
        // Find focusable elements
        $xpath = new DOMXPath($this->dom);
        $focusableElements = $xpath->query(
            'descendant::a|descendant::button|descendant::input|descendant::select|'.
                'descendant::textarea|descendant::*[@tabindex="0"]',
            $dialog
        );

        if ($focusableElements->length > 0) {
            // Add focus trap
            $firstFocusable = $focusableElements->item(0);
            $lastFocusable = $focusableElements->item($focusableElements->length - 1);

            if ($firstFocusable instanceof DOMElement && $lastFocusable instanceof DOMElement) {
                $firstFocusable->setAttribute('data-first-focusable', 'true');
                $lastFocusable->setAttribute('data-last-focusable', 'true');
            }
        }
    }

    /**
     * Check if element is visible to screen readers
     */
    protected function isVisibleToScreenReader(DOMElement $element): bool
    {
        // Check for hidden attribute
        if (
            $element->hasAttribute('hidden') ||
            $element->hasAttribute('aria-hidden') && $element->getAttribute('aria-hidden') === 'true'
        ) {
            return false;
        }

        // Check for display:none or visibility:hidden via class
        $class = $element->getAttribute('class');
        if (strpos($class, 'hidden') !== false || strpos($class, 'invisible') !== false) {
            return false;
        }

        return true;
    }

    /**
     * Get accessible name for an element
     */
    protected function getAccessibleName(DOMElement $element): string
    {
        try {
            // Check aria-label
            if ($element->hasAttribute('aria-label')) {
                return $this->ensureUtf8s($element->getAttribute('aria-label'));
            }

            // Check aria-labelledby
            if ($element->hasAttribute('aria-labelledby')) {
                $labelId = $element->getAttribute('aria-labelledby');
                $labelElement = $this->dom->getElementById($labelId);
                if ($labelElement) {
                    return $this->ensureUtf8s($labelElement->textContent);
                }
            }

            // Default to element content
            return $this->ensureUtf8s($element->textContent);
        } catch (\Exception $e) {
            error_log("Failed to get accessible name: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Get element states and properties
     */
    protected function getElementState(DOMElement $element): array
    {
        $state = [];

        // Check common ARIA states
        $ariaStates = ['expanded', 'selected', 'checked', 'pressed', 'current', 'invalid'];
        foreach ($ariaStates as $ariaState) {
            if ($element->hasAttribute('aria-'.$ariaState)) {
                $state[$ariaState] = $element->getAttribute('aria-'.$ariaState);
            }
        }

        return $state;
    }
}
