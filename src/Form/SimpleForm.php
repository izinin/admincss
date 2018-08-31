<?php

namespace Drupal\admin_css\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * SimpleForm with some custom functions and settings.
 */

class SimpleForm extends ConfigFormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['custom_css'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Enter your custom css'),
      '#default_value' => $this->config('admin_css.settings')->get('custom_css'),
      '#rows' => 10,
   ];
    return parent::buildForm($form, $form_state);
  }

  /**
  * Form submission handler.
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('admin_css.settings');
    $config->set('custom_css', $form_state->getValue('custom_css'));
    $config->save();
    $cur_data = $this->config('admin_css.settings')->get('custom_css');
    $file = file_save_data($cur_data, "public://admin-style.css", FILE_EXISTS_REPLACE);
    parent::submitForm($form, $form_state);

  }

}
