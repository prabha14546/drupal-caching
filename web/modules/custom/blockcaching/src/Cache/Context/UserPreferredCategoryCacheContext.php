<?php
namespace Drupal\blockcaching\Cache\Context;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class UserPreferredCategoryCacheContext implements CacheContextInterface {

  protected AccountProxyInterface $currentUser;
  protected EntityTypeManagerInterface $entityTypeManager;

  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function getLabel() {
    return t('User preferred category');
  }

  public function getContext() {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    return $user ? $user->get('field_preferred_category')->target_id : 'none';
  }

  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }
}
