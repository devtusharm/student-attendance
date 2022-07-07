<?php

namespace Drupal\student_attendance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin configurtion form for student attendance.
 */
class StudentAttendanceConfigurationForm extends ConfigFormBase{

  /*
   * Config settings
   *
   * @var number
   */
  const SETTINGS = 'student_attendance.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'student_attendance_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['student_attendance_sheet_row'] = [
      '#type' => 'number',
      '#title' => $this->t('Student Attendance Sheet Row'),
      '#default_value' => $config->get('student_attendance_sheet_row'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config(static::SETTINGS)->set(
      'student_attendance_sheet_row', $form_state->getValue('student_attendance_sheet_row')
    )->save();

    parent::buildForm($form, $form_state);

  }

}
