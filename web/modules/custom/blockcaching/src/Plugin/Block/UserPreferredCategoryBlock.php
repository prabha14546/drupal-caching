<?php

namespace Drupal\blockcaching\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a block that displays articles from the user's preferred category.
 *
 * @Block(
 *   id = "user_preferred_category_block",
 *   admin_label = @Translation("User Preferred Category"),
 * )
 */
class UserPreferredCategoryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PreferredCategoryArticlesBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal\user\Entity\User::load($this->currentUser->id());
    $preferred_category_id = NULL;

    if ($user->hasField('field_preferred_category') && !$user->get('field_preferred_category')->isEmpty()) {
      $preferred_category_id = $user->get('field_preferred_category')->target_id;
    }

    if (!$preferred_category_id) {
      return [
        '#markup' => $this->t('No preferred category selected.'),
      ];
    }

    // Load articles from the preferred category.
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('status', 1)
      ->condition('type', 'article')
      ->condition('field_preferred_category', $preferred_category_id)
      ->sort('created', 'DESC')
      ->range(0, 5)
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $articles = $storage->loadMultiple($nids);

    if (empty($articles)) {
      return [
        '#markup' => $this->t('No articles found for your preferred category.'),
        '#cache' => [
          'tags' => ['node_list'],
          'contexts' => ['user', 'user_preferred_category'],
        ],
      ];
    }

    $items = [];
    foreach ($articles as $article) {
      $items[] = [
        '#markup' => $article->toLink()->toString(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'tags' => ['node_list'],
        'contexts' => ['user', 'user_preferred_category'],
      ],
    ];
  }
}
