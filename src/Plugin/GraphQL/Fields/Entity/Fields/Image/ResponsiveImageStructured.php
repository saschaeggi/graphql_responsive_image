<?php
namespace Drupal\graphql_responsive_image\Plugin\GraphQL\Fields\Entity\Fields\Image;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Entity\File;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Returns responsive image infos.
 *
 * @GraphQLField(
 *   id = "responsive_image_structured",
 *   secure = true,
 *   name = "ResponsiveImageStructured",
 *   arguments = {
 *     "style" = "ResponsiveImageStyleId!"
 *   },
 *   type = "Map",
 *   field_types = {"image"},
 *   provider = "image",
 *   deriver = "Drupal\graphql_core\Plugin\Deriver\Fields\EntityFieldPropertyDeriver"
 * )
 */
class ResponsiveImageStructured extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Maps the image attributes to the provided structure.
   *
   * @var array
   */
  protected $dataStructure = [
    'srcset',
    'media',
    'type',
  ];

  /**
   * Renderer instance to render fields.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an ImageResponsive object.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $pluginId
   *   Id of the plugin.
   * @param array $pluginDefinition
   *   Plugin definition array.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $pluginId, array $pluginDefinition, RendererInterface $renderer) {
    $this->renderer = $renderer;
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
        $configuration,
        $pluginId,
        $pluginDefinition,
        $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {

    if ($value instanceof ImageItem) {
       $image = $value->getValue();
       $file = File::load($image['target_id']);

        // Get image attributes.
        $variables = [
          'uri' => $file->getFileUri(),
          'width' => $image['width'],
          'height' => $image['height'],
        ];

        $sources = [];

        // Load given image style.
        $responsive_image_style = ResponsiveImageStyle::load($args['style']);

        // Load all defined breakpoints.
        $breakpoints = array_reverse(\Drupal::service('breakpoint.manager')->getBreakpointsByGroup($responsive_image_style->getBreakpointGroup()));

        // Create source for each breakpoint.
        foreach ($responsive_image_style->getKeyedImageStyleMappings() as $breakpoint_id => $multipliers) {
          if (isset($breakpoints[$breakpoint_id])) {
            $sources[] =  _responsive_image_build_source_attributes($variables, $breakpoints[$breakpoint_id], $multipliers);
          }
        }

        // Map responsive image data to data structure.
        foreach ($sources as $field => $source) {
          foreach ($this->dataStructure as $name) {
            $structured_sources[$field][$name] = isset($source->storage()[$name]) ? $source->storage()[$name]->value() : '';
          }
        }

        // Create Uri from the fallback image style.
        $fallback = $responsive_image_style->getFallbackImageStyle();
        if (isset($fallback)) {
          $fallback_file_url = ImageStyle::load($fallback)->buildUri($file->getFileUri());
          // Return root-relative URL for fallback image.
          if (isset($fallback_file_url)) {
            $fallback_url = file_url_transform_relative(file_create_url($fallback_file_url));
          }
        }

        // Add fallback image and sources to return array.
        $results = [
          "image" =>  [
            "url" => $fallback_url,
            "alt" => $image['alt']
          ],
          "sources" => $structured_sources
        ];

        yield $results;

    }
  }

}
