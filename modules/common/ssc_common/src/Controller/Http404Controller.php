<?php

namespace Drupal\ssc_common\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Controller for default HTTP 4xx responses.
 */
class Http404Controller extends ControllerBase implements ContainerInjectionInterface {

  protected $renderer;

  protected $blockManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   */
  public function __construct(RendererInterface $renderer, BlockManagerInterface $blockManager) {
    $this->renderer = $renderer;
    $this->blockManager = $blockManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * The default 404 content.
   *
   * @return array
   *   A render array containing the message to display for 404 pages.
   */
  public function on404() {
    $view_id = 'site_search';
    $display_id = 'block_3';

    $view = Views::getView($view_id);
    $view->setDisplay($display_id);
    $view->initHandlers();

    $form_state = new \Drupal\Core\Form\FormState();
    $form_state->setFormState([
      'view' => $view,
      'display' => $view->display_handler->display,
      'exposed_form_plugin' => $view->display_handler->getPlugin('exposed_form'),
      'rerender' => TRUE,
      'no_redirect' => TRUE,
      'always_process' => TRUE,
    ]);
    $form_state->setMethod('get');
    $form = \Drupal::formBuilder()->buildForm('Drupal\views\Form\ViewsExposedForm', $form_state);

    // 404 Fallback message.
    $header = '
    <h1>' . $this->t("Page not found") . '</h1>
    <div class="box">
      <div class="row">
        <div class="col-xs-9 col-sm-10 col-md-10">
          <p>' . $this->t('This page may have been moved or deleted.') . '</p>
        </div>
      </div>
    </div>';

    return [
      '#type' => 'container',
      '#markup' => $header,
      '#attributes' => [
        'class' => '404 error',
      ],
      'form' => $form,
      '#weight' => 0,
    ];
  }

}
