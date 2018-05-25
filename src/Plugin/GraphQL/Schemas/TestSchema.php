<?php

namespace Drupal\graphql\Plugin\GraphQL\Schemas;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @GraphQLSchema(
 *   id = "test",
 *   name = "test",
 *   path = "/graphql-test"
 * )
 */
class TestSchema extends SchemaDefinitionLanguagePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function getSchemaDefinition() {
    return <<<GQL
      schema {
        query: Query
      }

      type Query {
        article(id: Int!): Article
        node(id: Int!): NodeInterface
      }

      interface NodeInterface {
        id: Int!
      }

      type Article implements NodeInterface {
        id: Int!
        title: String!
      }
GQL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getResolverRegistry() {
    $registry = new ResolverRegistry([
      'Query' => [
        'node' => function ($value, $args, ResolveContext $context, ResolveInfo $info) {
          return Node::load($args['id']);
        },
        'article' => function ($value, $args, ResolveContext $context, ResolveInfo $info) {
          if (($node = Node::load($args['id'])) && $node->bundle() === 'article') {
            return $node;
          }

          return NULL;
        },
      ],
      'Article' => [
        'id' => function (NodeInterface $value, $args, ResolveContext $context, ResolveInfo $info) {
          return $value->id();
        },
        'title' => function (NodeInterface $value, $args, ResolveContext $context, ResolveInfo $info) {
          return $value->label();
        },
      ],
    ], [
      'NodeInterface' => function (NodeInterface $value, ResolveContext $context, ResolveInfo $info) {
        if ($value->bundle() === 'article') {
          return 'Article';
        }

        return NULL;
      },
    ]);

    return $registry;
  }


}
