<?php

namespace Drupal\Tests\graphql\Kernel\Framework;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Test contextual language negotiation.
 */
class LanguageContextTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('configurable_language');
    $this->installConfig(['language']);
    $this->container->get('language_negotiator')
      ->setCurrentUser($this->accountProphecy->reveal());

    ConfigurableLanguage::create([
      'id' => 'fr',
      'weight' => 1,
    ])->save();


    $this->mockType('node', ['name' => 'Node']);

    $this->mockField('edge', [
      'name' => 'edge',
      'parents' => ['Root', 'Node'],
      'type' => 'Node',
      'arguments' => [
        'language' => 'String',
      ],
      'contextual_arguments' => ['language'],
    ], 'foo');

    $this->mockField('language', [
      'name' => 'language',
      'parents' => ['Root', 'Node'],
      'type' => 'String',
      'response_cache_contexts' => ['languages:language_interface'],
    ], function () {
      yield \Drupal::languageManager()->getCurrentLanguage()->getId();
    });


    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Test that the test setup is correct.
   */
  public function testTestSetup() {
    $this->assertTrue($this->container->get('language_manager')->isMultilingual(), 'Setup is not multilingual.');
  }

  /**
   * Test if the language negotiator is injected properly.
   */
  public function testNegotiatorInjection() {
    $methods = $this->container->get('language_negotiator')->getNegotiationMethods(LanguageInterface::TYPE_INTERFACE);
    $this->assertEquals('language-graphql', array_keys($methods)[0], 'GraphQL is not the first negotiator.');
  }

  /**
   * Test negotiator initialization.
   */
  public function testNegotiatorInitialization() {
    $context = $this->container->get('graphql.language_context');

    $result =$context->executeInLanguageContext(function () {
      return $this->container->get('language_negotiator')->initializeType(LanguageInterface::TYPE_INTERFACE);
    }, 'fr');

    $this->assertEquals('language-graphql', array_keys($result)[0]);
    $this->assertEquals('fr', $result['language-graphql']->getId());
  }

  /**
   * Test the language context service.
   */
  public function testLanguageContext() {
    $context = $this->container->get('graphql.language_context');

    $this->assertEquals('fr', $context->executeInLanguageContext(function () {
      return \Drupal::service('graphql.language_context')->getCurrentLanguage();
    }, 'fr'), 'Unexpected language context result.');
  }

  /**
   * Test the language negotiation within a context.
   */
  public function testLanguageNegotiation() {
    $context = $this->container->get('graphql.language_context');

    $this->assertEquals('fr', $context->executeInLanguageContext(function () {
      return \Drupal::service('language_manager')->getCurrentLanguage()->getId();
    }, 'fr'), 'Unexpected language negotiation result.');
  }

  /**
   * Test root language.
   */
  public function testRootLanguage() {
    $query = <<<GQL
query {
  language
}
GQL;
    $this->assertResults($query, [], [
      'language' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
    ], $this->defaultCacheMetaData());

  }

  /**
   * Test inherited language.
   */
  public function testInheritedLanguage() {
    $query = <<<GQL
query {
  edge(language: "fr") {
    language
  }
}
GQL;

    $this->assertResults($query, [], [
      'edge' => [
        'language' => 'fr',
      ],
    ], $this->defaultCacheMetaData());
  }

  /**
   * Test overridden language.
   */
  public function testOverriddenLanguage() {
    $query = <<<GQL
query {
  edge(language: "fr") {
    language
    edge(language: "en") {
      language
    }
  }
}
GQL;

    $this->assertResults($query, [], [
      'edge' => [
        'language' => 'fr',
        'edge' => [
          'language' => 'en',
        ],
      ],
    ], $this->defaultCacheMetaData());
  }

}
