<?php

namespace Drupal\graphql\Plugin\GraphQL\Schemas;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\SchemaPluginInterface;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Utils\BuildSchema;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class SchemaDefinitionLanguagePluginBase extends PluginBase implements SchemaPluginInterface, ContainerFactoryPluginInterface, CacheableDependencyInterface {
  use RefinableCacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * SchemaDefinitionLanguagePluginBase constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   */
  public function __construct(
    $configuration,
    $pluginId,
    $pluginDefinition
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $registry = $this->getResolverRegistry();
    $schema = BuildSchema::build($this->getSchemaDocument(), function ($config, TypeDefinitionNode $type, $map) use ($registry) {
      if ($type instanceof ObjectTypeDefinitionNode) {
        $config['resolveField'] = [$registry, 'resolveField'];
      }
      else if ($type instanceof InterfaceTypeDefinitionNode || $type instanceof UnionTypeDefinitionNode) {
        $config['resolveType'] = [$registry, 'resolveType'];
      }

      return $config;
    });

    return $schema;
  }

  /**
   * Retrieves the parsed AST of the schema definition.
   *
   * @return \GraphQL\Language\AST\DocumentNode
   *   The parsed schema document.
   */
  protected function getSchemaDocument() {
    // @TODO: Add caching of the parsed document.
    return Parser::parse($this->getSchemaDefinition());
  }

  /**
   * Retrieves the raw schema definiton string.
   *
   * @return string
   *   The schema definition.
   */
  abstract protected function getSchemaDefinition();

  /**
   * Retrieves the field and type resolver registry for the schema.
   *
   * @return \Drupal\graphql\GraphQL\ResolverRegistryInterface
   *   The field and type resolver registry.
   */
  abstract protected function getResolverRegistry();

}
