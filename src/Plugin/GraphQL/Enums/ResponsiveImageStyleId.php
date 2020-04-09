<?php

namespace Drupal\graphql_responsive_image\Plugin\GraphQL\Enums;

use Drupal\graphql\Plugin\GraphQL\Enums\EnumPluginBase;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * EnumPlugin.
 *
 * @GraphQLEnum(
 *   id = "responsive_image_style_id",
 *   name = "ResponsiveImageStyleId",
 *   provider = "image"
 * )
 */
class ResponsiveImageStyleId extends EnumPluginBase {

  /**
   * Public Function buildEnumValues()
   */
  public function buildEnumValues($definition) {
    $items = [];
    foreach (ResponsiveImageStyle::loadMultiple() as $responsiveImageStyle) {
      $items[$responsiveImageStyle->id()] = [
        'value' => $responsiveImageStyle->id(),
        'name' => $responsiveImageStyle->id(),
        'description' => $responsiveImageStyle->label(),
      ];
    }
    return $items;
  }

}
