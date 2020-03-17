<?php

namespace Drupal\admin_css\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SimpleForm with some custom functions and settings.
 */
class SimpleForm extends ConfigFormBase {

  /**
   * The CSS asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * The JavaScript asset collection optimizer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionOptimizerInterface
   */
  protected $jsCollectionOptimizer;

  /**
   * Constructs a PerformanceForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AssetCollectionOptimizerInterface $css_collection_optimizer, AssetCollectionOptimizerInterface $js_collection_optimizer) {
    parent::__construct($config_factory);

    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_css_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'admin_css.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['custom_css'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter your custom css'),
      '#default_value' => $this->config('admin_css.settings')->get('custom_css'),
      '#rows' => 10,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('admin_css.settings');
    $custom_css = $form_state->getValue('custom_css');
    $config->set('custom_css', $custom_css);
    $config->save();

    if (file_save_data($custom_css, 'public://admin-style.css', FileSystemInterface::EXISTS_REPLACE)) {
      // Flush the css/js asset cache.
      $this->flushAssetCache();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Flush the asset cache.
   *
   * @see drupal_flush_all_caches()
   */
  protected function flushAssetCache() {
    // Flush the CSS and JS asset file caches.
    $this->cssCollectionOptimizer->deleteAll();
    $this->jsCollectionOptimizer->deleteAll();
    _drupal_flush_css_js();
  }

}
