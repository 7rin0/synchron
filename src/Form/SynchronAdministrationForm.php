<?php

namespace Drupal\synchron\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Synchron Administration Form.
 */
class SynchronAdministrationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synchron_administration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'synchron.administration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('synchron.administration');
    $synchronService = \Drupal::service('synchron');
    $databases = $synchronService->sitesExtractorDatabase();

    $form['synchronization_from'] = [
      '#type' => 'select',
      '#title' => $this->t('Synchronization from site:'),
      '#options' => $databases['#options'],
      '#default_value' => $config->get('synchronization_from'),
    ];
    $form['synchronization_to'] = [
      '#type' => 'select',
      '#title' => $this->t('Synchronization to site:'),
      '#options' => $databases['#options'],
      '#default_value' => $config->get('synchronization_to'),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('synchron.administration');
    $config->set('synchronization_from', $form_state->getValue('synchronization_from'));
    $config->set('synchronization_to', $form_state->getValue('synchronization_to'))
      ->save();
    // TODO! Purge config cache after changes.
    parent::submitForm($form, $form_state);
  }

}
