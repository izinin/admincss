<?php

namespace Drupal\admincss\Form;

use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin CSS editor form.
 */
class AdminCssEditor extends ConfigFormBase {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS asset collection optimizer service.
   * @param \Drupal\Core\Asset\AssetCollectionOptimizerInterface $js_collection_optimizer
   *   The JavaScript asset collection optimizer service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    AssetCollectionOptimizerInterface $css_collection_optimizer,
    AssetCollectionOptimizerInterface $js_collection_optimizer
  ) {
    parent::__construct($config_factory);

    $this->fileSystem = $file_system;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
    $this->jsCollectionOptimizer = $js_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('asset.css.collection_optimizer'),
      $container->get('asset.js.collection_optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admincss_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'admincss.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['custom_css'] = [
      '#prefix' => '<div class="admincss-ace-editor">',
      '#suffix' => '</div>',
      '#type' => 'textarea',
      '#title' => $this->t('Custom CSS'),
      '#description' => $this->t('The custom CSS code.'),
      '#default_value' => $this->config('admincss.settings')->get('custom_css'),
      '#rows' => 10,
      '#attributes' => [
        'class' => [
          'admincss__editor',
          'admincss__custom-css',
        ],
        'data-ace-mode' => 'css',
      ],
    ];

    $form['#attached']['library'][] = 'admincss/admincss.editor';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('admincss.settings');
    $custom_css = $form_state->getValue('custom_css');
    $config->set('custom_css', $custom_css);
    $config->save();
    $changed = FALSE;
    $destination_uri = 'public://admin-style.css';
    if (empty($custom_css)) {
      // Empty CSS, delete the file.
      try {
        $this->fileSystem->delete($destination_uri);
        $changed = TRUE;
      }
      catch (FileException $e) {
        // Ignore and continue.
      }
    }
    elseif (file_save_data($custom_css, $destination_uri, FileSystemInterface::EXISTS_REPLACE)) {
      $changed = TRUE;
    }

    if ($changed) {
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