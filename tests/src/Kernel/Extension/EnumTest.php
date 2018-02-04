<?php

namespace Drupal\Tests\graphql\Kernel\Extension;

use Drupal\graphql\GraphQL\Utility\TypeCollector;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Youshido\GraphQL\Type\Enum\EnumType;

/**
 * Test enumeration support in different ways.
 *
 * @group graphql
 */
class EnumTest extends GraphQLTestBase {

  public static $modules = [
    'graphql_enum_test',
  ];

  /**
   * Test enumeration plugins.
   */
  public function testEnumPlugins() {
    $query = $this->getQuery('enums.gql');
    $this->assertResults($query, [], [
      'number' => 'ONE',
      'numbers' => [
        'ONE', 'TWO', 'THREE',
      ],
    ], $this->defaultCacheMetaData());
  }

  /**
   * Test enum type names.
   */
  public function testEnumTypeNames() {
    /** @var \Youshido\GraphQL\Schema\AbstractSchema $schema */
    $schema = \Drupal::service('plugin.manager.graphql.schema')->createInstance('default')->getSchema();
    $types = TypeCollector::collectTypes($schema);
    foreach ($types as $type) {
      if ($type instanceof EnumType && $type->getName() === NULL) {
        $this->fail('Unnamed enum type found.');
      }
    }
  }

}
