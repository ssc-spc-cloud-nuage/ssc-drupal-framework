<?php

namespace Drupal\ssc_common\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Annotation\ViewsField;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a Views field that displays breadcrumbs for a node.
 *
 * @ViewsField("breadcrumbs_field")
 */
class Breadcrumbs extends FieldPluginBase {

  protected $entityTypeManager;
  protected $breadcrumbBuilder;
  protected $renderer;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, BreadcrumbBuilderInterface $breadcrumb_builder, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->breadcrumbBuilder = $breadcrumb_builder;
    $this->renderer = $renderer;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('breadcrumb'),
      $container->get('renderer')
    );
  }

  public function defineOptions() {
    $options = parent::defineOptions();
    $options['crumb_index'] = ['default' => 0];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['crumb_index'] = [
      '#type' => 'number',
      '#title' => $this->t('Breadcrumb position'),
      '#description' => $this->t('Index of the breadcrumb to display. 0 = first (usually Home), 1 = second, etc.'),
      '#default_value' => $this->options['crumb_index'],
      '#min' => 0,
    ];
  }

  public function render(ResultRow $values) {
    $nid = $this->getValue($values);
    if (empty($nid)) {
      return ['#markup' => ''];
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node) {
      return ['#markup' => ''];
    }

    $url = $node->toUrl();
    $request = Request::create($url->toString());

    try {
      $router = \Drupal::service('router.no_access_checks');
      $attributes = $router->matchRequest($request);
      $request->attributes->add($attributes);

      $route_match = \Drupal\Core\Routing\RouteMatch::createFromRequest($request);
      $breadcrumb = $this->breadcrumbBuilder->build($route_match);

      $links = $breadcrumb->getLinks();
      $index = (int) $this->options['crumb_index'];

      if (isset($links[$index])) {
        return ['#markup' => $links[$index]->getText()];
      }
      else {
        return ['#markup' => ''];
      }
    }
    catch (\Exception $e) {
      return ['#markup' => ''];
    }
  }

}
