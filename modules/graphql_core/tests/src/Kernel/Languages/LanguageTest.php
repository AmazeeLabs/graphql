<?php

namespace Drupal\Tests\graphql_core\Kernel\Languages;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Test multilingual behavior of `graphql_core` features.
 *
 * @group graphql_core
 */
class LanguageTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'graphql',
    'graphql_core',
    'graphql_context_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installconfig(['language']);
    $this->installEntitySchema('configurable_language');
    $this->container->get('router.builder')->rebuild();

    ConfigurableLanguage::create([
      'id' => 'fr',
      'weight' => 1,
    ])->save();

    ConfigurableLanguage::create([
      'id' => 'es',
      'weight' => 2,
    ])->save();

    ConfigurableLanguage::create([
      'id' => 'pt-br',
      'weight' => 3,
    ])->save();

    $config = $this->config('language.negotiation');
    $config->set('url.prefixes', ['en' => 'en', 'es' => 'es', 'fr' => 'fr'])
      ->save();

    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Test listing of available languages.
   */
  public function testLanguageId() {
    $metadata = $this->defaultCacheMetaData();

    // TODO: Check cache metadata.
    $metadata->addCacheTags([
      'entity_bundles',
      'entity_field_info',
      'entity_types',
    ]);

    $this->assertResults($this->getQueryFromFile('languages.gql'), [], [
      'languages' => [
        0 => [
          'id' => 'en',
          'name' => 'English',
          'isDefault' => TRUE,
          'isLocked' => FALSE,
          'direction' => 'ltr',
          'weight' => 0,
          'argument' => 'en',
        ],

        1 => [
          'id' => 'fr',
          'name' => 'French',
          'isDefault' => FALSE,
          'isLocked' => FALSE,
          'direction' => 'ltr',
          'weight' => 1,
          'argument' => 'fr',
        ],

        2 => [
          'id' => 'es',
          'name' => 'Spanish',
          'isDefault' => FALSE,
          'isLocked' => FALSE,
          'direction' => 'ltr',
          'weight' => 2,
          'argument' => 'es',
        ],
        3 => [
          'id' => 'pt-br',
          'name' => 'Portuguese, Brazil',
          'isDefault' => FALSE,
          'isLocked' => FALSE,
          'direction' => 'ltr',
          'weight' => 3,
          'argument' => 'pt_br',
        ],
      ],
    ], $metadata);
  }

}
