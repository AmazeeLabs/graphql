<?php

namespace Drupal\graphql\GraphQL;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use GraphQL\Type\Definition\ResolveInfo;

class ResolverRegistry {

  /**
   * Nested list of field resolvers.
   *
   * Contains a nested list of callables, keyed by type and field name.
   *
   * @var callable[][]
   */
  protected $fieldResolvers;

  /**
   * List of type resolvers for abstract types.
   *
   * Contains a list of callables keyed by the name of the abstract type.
   *
   * @var callable[]
   */
  protected $typeResolvers;

  /**
   * ResolverRegistry constructor.
   *
   * @param $fieldResolvers
   * @param $typeResolvers
   */
  public function __construct($fieldResolvers, $typeResolvers) {
    $this->fieldResolvers = $fieldResolvers;
    $this->typeResolvers = $typeResolvers;
  }

  /**
   * @param string $type
   * @param string $field
   * @param callable $resolver
   *
   * @return $this
   */
  public function addFieldResolver($type, $field, callable $resolver) {
    $this->fieldResolvers[$type][$field] = $resolver;
    return $this;
  }

  /**
   * @param $abstract
   * @param $type
   * @param callable $condition
   *
   * @return $this
   */
  public function addTypeResolver($abstract, $type, callable $condition) {
    $this->typeResolvers[$abstract][$type] = $condition;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveField($value, $args, ResolveContext $context, ResolveInfo $info) {
    if (isset($this->fieldResolvers[$info->parentType->name][$info->fieldName])) {
      $resolve = $this->fieldResolvers[$info->parentType->name][$info->fieldName];
      return $resolve($value, $args, $context, $info);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveType($value, ResolveContext $context, ResolveInfo $info) {
    /** @var \GraphQL\Type\Definition\InterfaceType|\GraphQL\Type\Definition\UnionType $abstract */
    $abstract = $info->returnType->getWrappedType(TRUE);

    if (isset($this->typeResolvers[$abstract->name])) {
      $resolve = $this->typeResolvers[$abstract->name];
      return $resolve($value, $context, $info);
    }

    return NULL;
  }

}