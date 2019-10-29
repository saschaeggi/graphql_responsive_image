<?php
namespace Drupal\graphql_responsive_image\Plugin\GraphQL\Fields\Entity\Fields\Image;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Retrieve the responsive image.
 *
 * @GraphQLField(
 *   id = "responsive_image",
 *   secure = true,
 *   name = "ResponsiveImage",
 *   type = "String",
 *   arguments = {
 *     "style" = "ResponsiveImageStyleId!"
 *   },
 *   field_types = {"image"},
 *   provider = "image",
 *   deriver = "Drupal\graphql_core\Plugin\Deriver\Fields\EntityFieldPropertyDeriver"
 * )
 */
class ResponsiveImage extends FieldPluginBase implements ContainerFactoryPluginInterface {

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
  public function __construct(array $configuration, $pluginId, $pluginDefinition, RendererInterface $renderer) {
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
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof ImageItem && $value->entity->access('view')) {

      $variables = [
          '#theme' => 'responsive_image',
          '#responsive_image_style_id' => $args['style'],
          '#uri' => $value->entity->getFileUri(),
      ];

      yield $this->renderer->render($variables);
    }
  }

}
