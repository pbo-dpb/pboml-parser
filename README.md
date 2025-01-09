# PBOML Parser
### A PHP library for parsing, validating, and rendering PBOML documents
_Un outil PHP pour l'analyse, la validation et le rendu des documents PBOML_

---

[English](#english) | [Français](#français)

<a name="english"></a>
## English

### Overview

PBOML Parser is a comprehensive PHP library designed to handle PBOML (Performance-Based Output Markup Language) documents. It provides robust functionality for parsing, validating, and rendering PBOML content with a strong focus on accessibility, SEO optimization, and multi-language support.

### Key Features

- **Multilingual Support**: Built-in support for English and French content
- **Accessibility First**: Comprehensive accessibility features and ARIA support
- **SEO Optimization**: Built-in SEO management and optimization
- **Flexible Rendering**: Support for multiple content types and presentation formats
- **Theme Support**: Light/dark mode and responsive design capabilities
- **Robust Validation**: Extensive validation system with detailed error reporting
- **Extensible Architecture**: Modular design allowing for easy extensions

### Content Types Supported

The library supports various content types including:
- Markdown content
- Data tables with advanced formatting
- SVG graphics with accessibility enhancements
- Interactive charts
- Bitmap images with responsive sizing
- HTML content with shadow DOM support
- Key-value lists
- Structured annotations
- Heading hierarchies

### System Requirements

- PHP 8.0 or higher
- DOM Extension
- JSON Extension
- Mbstring Extension
- libxml Extension

### Installation

```bash
composer require pbo/pboml-parser
```

### Basic Usage

```php
use PBO\PbomlParser\Parser\PBOMLParser;
use PBO\PbomlParser\Generator\HTMLGenerator;

// Initialize parser and generator
$parser = new PBOMLParser();
$generator = new HTMLGenerator();

// Parse PBOML content
$parsed = $parser->parse($pbomlContent);

// Generate HTML
$html = $generator->generate($parsed);
```

### Advanced Configuration

#### Setting Strict Mode
```php
$parser->setStrictMode(true);
```

#### Configuring SEO
```php
$seoManager = new SEOManager([
    'site_name' => 'My Site',
    'twitter_handle' => '@mysite',
    'publisher_name' => 'Publisher Name'
]);
```

#### Customizing Accessibility
```php
$accessibilityManager = new AccessibilityManager([
    'log_warnings' => true
]);
```

### Content Structure

PBOML documents are structured as follows:

```yaml
pboml:
  version: "1.0.0"
document:
  id: "DOC-ID"
  title:
    en: "Document Title"
    fr: "Titre du document"
  type:
    en: "Document Type"
    fr: "Type de document"
slices:
  - type: "heading"
    content:
      en: "Section Heading"
      fr: "En-tête de section"
  # Additional slices...
```

### Validation System

The library implements a comprehensive validation system:

1. **Root Structure Validation**: Ensures proper document structure
2. **Document Metadata Validation**: Validates metadata requirements
3. **Slice Validation**: Type-specific validation for each content slice
4. **Localization Validation**: Ensures proper multilingual content
5. **Presentation Validation**: Validates presentation attributes

### Error Handling

The library provides specialized exceptions for different error types:

- `ValidationException`: For validation errors
- `ParsingException`: For parsing errors
- `RenderingException`: For rendering errors
- `EncodingException`: For encoding issues

### Rendering System

The rendering system supports multiple output formats and includes:

1. **Slice Renderers**: Specialized renderers for each content type
2. **Theme Support**: Light/dark mode theming
3. **Responsive Design**: Adaptive layouts and container queries
4. **Print Styling**: Optimized print layouts

### Customization and Extension

#### Creating Custom Slice Types
```php
class CustomSliceProcessor extends BaseSliceProcessor
{
    public function process(array $slice): array
    {
        // Implementation
    }
}

// Register the processor
$sliceProcessor->registerProcessor('custom', new CustomSliceProcessor());
```

#### Adding Custom Validators
```php
class CustomValidator extends BaseValidator implements ValidatorInterface
{
    public function validate(array $data): bool
    {
        // Implementation
    }
}
```

### Accessibility Features

The library includes numerous accessibility features:

- ARIA attribute management
- Heading hierarchy validation
- Skip links generation
- Screen reader optimizations
- Keyboard navigation support
- Alternative text management

### Best Practices

1. **Content Organization**
   - Use appropriate slice types for content
   - Maintain consistent heading hierarchy
   - Provide translations for all content

2. **Validation**
   - Enable strict mode during development
   - Handle validation errors appropriately
   - Validate localization completeness

3. **Rendering**
   - Use semantic HTML elements
   - Implement proper ARIA attributes
   - Ensure responsive design compatibility

### Performance Considerations

- Implement caching for parsed documents
- Use lazy loading for images
- Optimize SVG content
- Minimize DOM operations
- Use efficient selectors

### Contributing

Contributions are welcome! Please read our contributing guidelines and submit pull requests to our repository.

### License

This project is licensed under the MIT License - see the LICENSE file for details.

---

<a name="français"></a>
## Français

### Vue d'ensemble

PBOML Parser est une bibliothèque PHP complète conçue pour gérer les documents PBOML (Performance-Based Output Markup Language). Elle fournit des fonctionnalités robustes pour l'analyse, la validation et le rendu du contenu PBOML avec un accent particulier sur l'accessibilité, l'optimisation SEO et le support multilingue.

### Fonctionnalités principales

- **Support multilingue**: Prise en charge intégrée du contenu en anglais et en français
- **Priorité à l'accessibilité**: Fonctionnalités d'accessibilité complètes et support ARIA
- **Optimisation SEO**: Gestion et optimisation SEO intégrées
- **Rendu flexible**: Prise en charge de plusieurs types de contenu et formats de présentation
- **Support des thèmes**: Capacités de mode clair/sombre et de design responsive
- **Validation robuste**: Système de validation extensif avec rapport d'erreurs détaillé
- **Architecture extensible**: Conception modulaire permettant des extensions faciles

### Types de contenu pris en charge

La bibliothèque prend en charge divers types de contenu, notamment:
- Contenu Markdown
- Tableaux de données avec formatage avancé
- Graphiques SVG avec améliorations d'accessibilité
- Graphiques interactifs
- Images bitmap avec dimensionnement responsive
- Contenu HTML avec support du DOM fantôme
- Listes clé-valeur
- Annotations structurées
- Hiérarchies de titres

### Prérequis système

- PHP 8.0 ou supérieur
- Extension DOM
- Extension JSON
- Extension Mbstring
- Extension libxml

### Installation

```bash
composer require pbo/pboml-parser
```

### Utilisation de base

```php
use PBO\PbomlParser\Parser\PBOMLParser;
use PBO\PbomlParser\Generator\HTMLGenerator;

// Initialiser l'analyseur et le générateur
$parser = new PBOMLParser();
$generator = new HTMLGenerator();

// Analyser le contenu PBOML
$parsed = $parser->parse($pbomlContent);

// Générer le HTML
$html = $generator->generate($parsed);
```

### Configuration avancée

#### Activer le mode strict
```php
$parser->setStrictMode(true);
```

#### Configurer le SEO
```php
$seoManager = new SEOManager([
    'site_name' => 'Mon Site',
    'twitter_handle' => '@monsite',
    'publisher_name' => 'Nom de l\'éditeur'
]);
```

#### Personnaliser l'accessibilité
```php
$accessibilityManager = new AccessibilityManager([
    'log_warnings' => true
]);
```

### Structure du contenu

Les documents PBOML sont structurés comme suit:

```yaml
pboml:
  version: "1.0.0"
document:
  id: "DOC-ID"
  title:
    en: "Document Title"
    fr: "Titre du document"
  type:
    en: "Document Type"
    fr: "Type de document"
slices:
  - type: "heading"
    content:
      en: "Section Heading"
      fr: "En-tête de section"
  # Tranches supplémentaires...
```

### Système de validation

La bibliothèque implémente un système de validation complet:

1. **Validation de la structure racine**: Assure une structure de document appropriée
2. **Validation des métadonnées**: Valide les exigences des métadonnées
3. **Validation des tranches**: Validation spécifique au type pour chaque tranche de contenu
4. **Validation de la localisation**: Assure un contenu multilingue approprié
5. **Validation de la présentation**: Valide les attributs de présentation

### Gestion des erreurs

La bibliothèque fournit des exceptions spécialisées pour différents types d'erreurs:

- `ValidationException`: Pour les erreurs de validation
- `ParsingException`: Pour les erreurs d'analyse
- `RenderingException`: Pour les erreurs de rendu
- `EncodingException`: Pour les problèmes d'encodage

### Système de rendu

Le système de rendu prend en charge plusieurs formats de sortie et inclut:

1. **Renderers de tranches**: Renderers spécialisés pour chaque type de contenu
2. **Support des thèmes**: Thèmes clair/sombre
3. **Design responsive**: Mises en page adaptatives et requêtes de conteneur
4. **Style d'impression**: Mises en page optimisées pour l'impression

### Personnalisation et extension

#### Créer des types de tranches personnalisés
```php
class CustomSliceProcessor extends BaseSliceProcessor
{
    public function process(array $slice): array
    {
        // Implémentation
    }
}

// Enregistrer le processeur
$sliceProcessor->registerProcessor('custom', new CustomSliceProcessor());
```

#### Ajouter des validateurs personnalisés
```php
class CustomValidator extends BaseValidator implements ValidatorInterface
{
    public function validate(array $data): bool
    {
        // Implémentation
    }
}
```

### Fonctionnalités d'accessibilité

La bibliothèque inclut de nombreuses fonctionnalités d'accessibilité:

- Gestion des attributs ARIA
- Validation de la hiérarchie des titres
- Génération de liens de saut
- Optimisations pour les lecteurs d'écran
- Support de la navigation au clavier
- Gestion du texte alternatif

### Meilleures pratiques

1. **Organisation du contenu**
   - Utiliser les types de tranches appropriés pour le contenu
   - Maintenir une hiérarchie de titres cohérente
   - Fournir des traductions pour tout le contenu

2. **Validation**
   - Activer le mode strict pendant le développement
   - Gérer les erreurs de validation de manière appropriée
   - Valider l'exhaustivité de la localisation

3. **Rendu**
   - Utiliser des éléments HTML sémantiques
   - Implémenter les attributs ARIA appropriés
   - Assurer la compatibilité avec le design responsive

### Considérations de performance

- Implémenter la mise en cache pour les documents analysés
- Utiliser le chargement paresseux pour les images
- Optimiser le contenu SVG
- Minimiser les opérations DOM
- Utiliser des sélecteurs efficaces

### Contribution

Les contributions sont les bienvenues ! Veuillez lire nos directives de contribution et soumettre des pull requests à notre dépôt.

### Licence

Ce projet est sous licence MIT - voir le fichier LICENSE pour plus de détails.

---

## Support

For support in English or French, please open an issue on our GitHub repository or contact our support team.

Pour obtenir de l'aide en français ou en anglais, veuillez ouvrir un ticket sur notre dépôt GitHub ou contacter notre équipe de support.