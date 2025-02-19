<?php

namespace Drupal\blockcaching\Plugin\Block;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\blockcaching\Plugin\Block\BlockCacheTags;

/**
 * Provides a block that displays the latest three article titles and user email.
 *
 * @Block(
 *   id = "block_user_context",
 *   admin_label = @Translation("Block User Context"),
 * )
 */
class BlockUserContext extends BlockCacheTags {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a BlockUserContext object.
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_type_manager, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
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
    // Call parent build() to get the latest articles block output.
    $build = parent::build();
    // Get the current user's email.
    $user_email = $this->currentUser->getEmail();
     // Append user email directly into the item list.
    $build['#items'][] = [
    '#markup' => '<p><strong>Your Email:</strong> ' . $user_email . '</p>',
    ];
    // Ensure caching varies per user.
    $build['#cache']['contexts'][] = 'user';

    return $build;
  }
}
