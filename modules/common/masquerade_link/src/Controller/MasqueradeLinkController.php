<?php

namespace Drupal\masquerade_link\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Masquerade Link routes.
 */
class MasqueradeLinkController extends ControllerBase {

  public function generateMasqueradeToken($user_id): JsonResponse {
    $token = \Drupal::csrfToken()->get('user/' . $user_id . '/masquerade');
    return new JsonResponse(['token' => $token]);
  }

}
